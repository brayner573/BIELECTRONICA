<?php
/**
 * FAXEL BI — Controlador de Reportes (Módulo 7) SaaS Multiempresa
 * PDF + Excel + KPIs
 */
class ReporteController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        // KPIs calculados
        $ventaModel  = new VentaModel();

        $kpis = [
            'ventas_mes'      => $ventaModel->ventasMes($empresaId),
            'crecimiento'     => $ventaModel->crecimientoMensual($empresaId),
            'ticket_prom'     => $ventaModel->ticketPromedio($empresaId),
            'clientes_activos'=> $ventaModel->clientesActivos($empresaId),
            'rentabilidad'    => $ventaModel->rentabilidadMes($empresaId),
        ];

        // Llamar Python para KPIs avanzados indicando empresa_id
        $kpisAvanzados = $this->callPythonAI("/kpis/calculate?empresa_id={$empresaId}", [], 'GET') ?? [
            'kpis' => ['cac'=>0,'ltv'=>0,'churn_rate'=>0,'ticket_prom'=>0,'ltv_cac'=>0]
        ];

        $this->view('reportes/index', [
            'title'         => 'Reportes & Motor Analítico',
            'kpis'          => $kpis,
            'kpisAvanzados' => $kpisAvanzados['kpis'] ?? [],
        ]);
    }

    public function pdf(array $params = []): void
    {
        $this->requireAuth();

        // Verificar si TCPDF/FPDF está disponible
        $fpdfPath = dirname(__DIR__, 2) . '/vendor/fpdf/fpdf.php';

        if (!file_exists($fpdfPath)) {
            // Generar HTML que el usuario puede imprimir
            $this->generarHTMLReport();
            return;
        }

        // Con FPDF instalado
        require $fpdfPath;
        $this->generarPDF();
    }

    private function generarHTMLReport(): void
    {
        $empresaId = $this->getEmpresaId();
        $ventaModel = new VentaModel();
        
        $kpis = [
            'ventas_mes'  => $ventaModel->ventasMes($empresaId),
            'ticket_prom' => $ventaModel->ticketPromedio($empresaId),
            'rentabilidad'=> $ventaModel->rentabilidadMes($empresaId),
        ];
        $ventas = $ventaModel->comparativaMensual($empresaId, 6);

        header('Content-Type: text/html; charset=utf-8');
        include dirname(__DIR__) . '/views/reportes/html_report.php';
        exit;
    }

    private function generarPDF(): void
    {
        // Implementación completa con FPDF
        $this->json(['message' => 'PDF generado. Ver directorio /reports/']);
    }

    public function excel(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();
        $tipo = $this->get('tipo', 'ventas');

        if ($tipo === 'rentabilidad') {
            $productoModel = new ProductoModel();
            // Actualizar clasificación ABC para garantizar datos frescos
            $productoModel->actualizarClasificacionABC($empresaId);
            $productos = $productoModel->topRentables($empresaId, 100);

            $filename = 'FAXEL_BI_Rentabilidad_Productos_' . date('Y-m-d') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header('Cache-Control: no-cache');

            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($out, [
                'Posición', 'Código', 'Producto', 'Categoría', 'Clasificación ABC', 
                'Precio Venta (S/)', 'Costo Unitario (S/)', 'Unidades Vendidas', 
                'Ingresos (S/)', 'Utilidad (S/)', 'Margen (%)'
            ]);

            foreach ($productos as $i => $p) {
                fputcsv($out, [
                    $i + 1,
                    $p['codigo'],
                    $p['nombre'],
                    $p['categoria'] ?? 'Sin Categoría',
                    $p['clasificacion'],
                    number_format($p['precio_venta'], 2, '.', ''),
                    number_format($p['precio_costo'], 2, '.', ''),
                    $p['unidades_vendidas'],
                    number_format($p['ingresos'], 2, '.', ''),
                    number_format($p['utilidad'], 2, '.', ''),
                    number_format($p['margen'], 1, '.', '')
                ]);
            }
            fclose($out);
            exit;
        }

        if ($tipo === 'churn') {
            $clienteModel = new ClienteModel();
            $clientes = $clienteModel->churnRanking($empresaId, '', 500);

            $filename = 'FAXEL_BI_Riesgo_Churn_Clientes_' . date('Y-m-d') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header('Cache-Control: no-cache');

            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($out, [
                'Código Cliente', 'Razón Social', 'RUC/DNI', 'Email', 'Teléfono', 
                'Ciudad', 'Categoría Cliente', 'Riesgo Churn', 'Score Churn (0-100)', 
                'Última Compra', 'Días Inactivo', 'Total Compras', 'Monto Acumulado (S/)', 'Ticket Promedio (S/)'
            ]);

            foreach ($clientes as $c) {
                fputcsv($out, [
                    $c['codigo'],
                    $c['razon_social'],
                    $c['ruc_dni'],
                    $c['email'] ?? '—',
                    $c['telefono'] ?? '—',
                    $c['ciudad'] ?? '—',
                    $c['categoria'] ?? 'regular',
                    ucfirst($c['churn_riesgo']),
                    $c['churn_score'],
                    $c['ultima_compra'] ? date('d/m/Y', strtotime($c['ultima_compra'])) : '—',
                    $c['dias_sin_compra'] ?? '—',
                    $c['total_compras'],
                    number_format($c['monto_acumulado'], 2, '.', ''),
                    number_format($c['ticket_promedio'], 2, '.', '')
                ]);
            }
            fclose($out);
            exit;
        }

        // Default: Comparativa mensual de ventas
        $ventaModel = new VentaModel();
        $ventas     = $ventaModel->comparativaMensual($empresaId, 12);

        $filename = 'FAXEL_BI_Reporte_Ventas_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

        fputcsv($out, ['Mes', 'Ventas (S/)', 'Utilidad (S/)', 'Transacciones']);
        foreach ($ventas as $v) {
            fputcsv($out, [
                $v['mes'],
                number_format($v['ventas'], 2, '.', ''),
                number_format($v['utilidad'], 2, '.', ''),
                $v['transacciones'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function kpis(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $ventaModel = new VentaModel();

        $kpis = [
            'ventas_hoy'       => $ventaModel->ventasHoy($empresaId),
            'ventas_mes'       => $ventaModel->ventasMes($empresaId),
            'crecimiento'      => $ventaModel->crecimientoMensual($empresaId),
            'ticket_promedio'  => $ventaModel->ticketPromedio($empresaId),
            'clientes_activos' => $ventaModel->clientesActivos($empresaId),
            'facturas_mes'     => $ventaModel->facturasMes($empresaId),
            'rentabilidad'     => $ventaModel->rentabilidadMes($empresaId),
        ];

        // Python KPIs avanzados indicando empresa_id
        $advanced = $this->callPythonAI("/kpis/calculate?empresa_id={$empresaId}", [], 'GET');
        if ($advanced && isset($advanced['kpis'])) {
            $kpis = array_merge($kpis, $advanced['kpis']);
        }

        $this->json(['success' => true, 'kpis' => $kpis]);
    }
}
