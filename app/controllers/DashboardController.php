<?php
/**
 * FAXEL BI — Controlador del Dashboard Ejecutivo SaaS Multiempresa (Módulo 1)
 */
class DashboardController extends Controller
{
    private VentaModel $ventaModel;

    public function __construct()
    {
        parent::__construct();
        $this->ventaModel = new VentaModel();
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        // Filtros de fecha
        $desde = $this->get('desde', date('Y-m-01'));
        $hasta = $this->get('hasta', date('Y-m-d'));

        // Cargar meta mensual dinámica desde la base de datos
        $db = Database::getInstance();
        $mes = (int)date('m');
        $anio = (int)date('Y');
        $stmtMeta = $db->prepare("SELECT SUM(meta_monto) FROM objetivos_sucursales WHERE empresa_id = ? AND mes = ? AND anio = ?");
        $stmtMeta->execute([$empresaId, $mes, $anio]);
        $metaDb = $stmtMeta->fetchColumn();
        $metaMes = $metaDb > 0 ? (float)$metaDb : (float)$this->config['meta']['ventas_mes'];

        // ── KPIs Ejecutivos ──────────────────────────────────
        $kpis = [
            'ventas_hoy'       => $this->ventaModel->ventasHoy($empresaId),
            'ventas_mes'       => $this->ventaModel->ventasMes($empresaId),
            'crecimiento'      => $this->ventaModel->crecimientoMensual($empresaId),
            'ticket_promedio'  => $this->ventaModel->ticketPromedio($empresaId, $desde, $hasta),
            'clientes_activos' => $this->ventaModel->clientesActivos($empresaId),
            'facturas_mes'     => $this->ventaModel->facturasMes($empresaId),
            'rentabilidad'     => $this->ventaModel->rentabilidadMes($empresaId),
            'meta_mes'         => $metaMes,
        ];

        // Meta vs resultado
        $kpis['meta_pct'] = $kpis['meta_mes'] > 0
            ? round(($kpis['ventas_mes'] / $kpis['meta_mes']) * 100, 1)
            : 0;

        // ── Datos de Gráficos ────────────────────────────────
        $charts = [
            'ventas_diarias'   => $this->ventaModel->ventasPorDia($empresaId, 30),
            'por_sucursal'     => $this->ventaModel->ventasPorSucursal($empresaId, $desde, $hasta),
            'por_categoria'    => $this->ventaModel->ventasPorCategoria($empresaId, $desde, $hasta),
            'heatmap_horario'  => $this->ventaModel->heatmapHorario($empresaId),
            'comparativa'      => $this->ventaModel->comparativaMensual($empresaId, 6),
            'ranking_clientes' => $this->ventaModel->rankingClientes($empresaId, 10, $desde, $hasta),
        ];

        // ── Alertas recientes ────────────────────────────────
        $alertaModel = new AlertaModel();
        $alertaModel->generarAlertasAutomaticas($empresaId);
        $alertas     = $alertaModel->getActivas($empresaId);
        $alertaStats = $alertaModel->contarPorEstado($empresaId);

        // ── Sucursales y filtros con aislamiento y metas ─────
        $stmt = $db->prepare("
            SELECT s.id, s.nombre, COALESCE(o.meta_monto, 0) AS meta_monto
            FROM sucursales s
            LEFT JOIN objetivos_sucursales o ON o.sucursal_id = s.id AND o.mes = ? AND o.anio = ?
            WHERE s.activo = 1 AND s.empresa_id = ?
        ");
        $stmt->execute([$mes, $anio, $empresaId]);
        $sucursales = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT id, razon_social FROM clientes WHERE activo = 1 AND empresa_id = ? ORDER BY razon_social");
        $stmt->execute([$empresaId]);
        $clientes = $stmt->fetchAll();

        $this->view('dashboard/index', [
            'title'      => 'Dashboard Ejecutivo',
            'kpis'       => $kpis,
            'charts'     => $charts,
            'alertas'    => $alertas,
            'alertaStats'=> $alertaStats,
            'sucursales' => $sucursales,
            'clientes'   => $clientes,
            'desde'      => $desde,
            'hasta'      => $hasta,
        ]);
    }

    // API: datos JSON para Chart.js (AJAX)
    public function apiCharts(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $tipo  = $this->get('tipo', 'ventas_diarias');
        $desde = $this->get('desde', date('Y-m-01'));
        $hasta = $this->get('hasta', date('Y-m-d'));

        $data = match ($tipo) {
            'ventas_diarias'   => $this->ventaModel->ventasPorDia($empresaId, 30),
            'por_sucursal'     => $this->ventaModel->ventasPorSucursal($empresaId, $desde, $hasta),
            'por_categoria'    => $this->ventaModel->ventasPorCategoria($empresaId, $desde, $hasta),
            'comparativa'      => $this->ventaModel->comparativaMensual($empresaId, 6),
            'ranking_clientes' => $this->ventaModel->rankingClientes($empresaId, 10, $desde, $hasta),
            'heatmap'          => $this->ventaModel->heatmapHorario($empresaId),
            default            => []
        };

        $this->json(['success' => true, 'data' => $data]);
    }

    // Guardar metas dinámicas (POST)
    public function guardarMetas(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $metas = $this->post('metas', []);
        $mes = (int)date('m');
        $anio = (int)date('Y');

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO objetivos_sucursales (empresa_id, sucursal_id, anio, mes, meta_monto)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE meta_monto = VALUES(meta_monto)
            ");

            foreach ($metas as $sucId => $monto) {
                $stmt->execute([$empresaId, (int)$sucId, $anio, $mes, (float)$monto]);
            }

            $db->commit();
            $this->json(['success' => true, 'message' => 'Metas mensuales actualizadas con éxito.']);
        } catch (Exception $e) {
            $db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
