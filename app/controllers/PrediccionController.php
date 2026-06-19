<?php
/**
 * FAXEL BI — Controlador de Predicción IA (Módulo 2) SaaS Multiempresa
 */
class PrediccionController extends Controller
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

        // Obtener predicciones guardadas de BD
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM predicciones
            WHERE empresa_id = ? AND tipo = 'ventas_7d' AND fecha_prediccion >= CURDATE()
            ORDER BY fecha_prediccion
        ");
        $stmt->execute([$empresaId]);
        $predicciones7d = $stmt->fetchAll();

        $stmt = $db->prepare("
            SELECT * FROM predicciones
            WHERE empresa_id = ? AND tipo IN ('ventas_7d','ventas_30d')
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$empresaId]);
        $ultimaPrediccion = $stmt->fetch() ?: null;

        // Serie histórica para mostrar en gráfico
        $historico = $this->ventaModel->serieTemporalVentas($empresaId, 90);

        $this->view('prediccion/index', [
            'title'           => 'Predicción Inteligente de Ventas',
            'predicciones7d'  => $predicciones7d,
            'ultimaPrediccion'=> $ultimaPrediccion,
            'historico'       => $historico,
        ]);
    }

    public function ejecutar(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('prediccion.train');
        $empresaId = $this->getEmpresaId();

        // Obtener serie temporal de ventas
        $serieVentas = $this->ventaModel->serieTemporalVentas($empresaId, 365);

        // Llamar al microservicio Python
        $resultado = $this->callPythonAI('/predict/sales', [
            'data'       => $serieVentas,
            'horizon_7'  => true,
            'horizon_30' => true,
            'empresa_id' => $empresaId,
        ]);

        if (!$resultado) {
            $this->json([
                'success' => false,
                'message' => 'Microservicio IA no disponible. Usando datos en caché.',
                'fallback' => true,
            ]);
            return;
        }

        // Guardar predicciones en BD
        $db = Database::getInstance();
        foreach ($resultado['predicciones_7d'] ?? [] as $pred) {
            $db->prepare("
                INSERT INTO predicciones (empresa_id, tipo, modelo, fecha_prediccion, valor_predicho, limite_inf, limite_sup, exactitud, mae, rmse)
                VALUES (?, 'ventas_7d', 'prophet', ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    valor_predicho = VALUES(valor_predicho),
                    limite_inf = VALUES(limite_inf),
                    limite_sup = VALUES(limite_sup)
            ")->execute([
                $empresaId,
                $pred['ds'],
                $pred['yhat'],
                $pred['yhat_lower'],
                $pred['yhat_upper'],
                $resultado['metricas']['r2'] ?? null,
                $resultado['metricas']['mae'] ?? null,
                $resultado['metricas']['rmse'] ?? null,
            ]);
        }

        $this->json([
            'success'    => true,
            'resultado'  => $resultado,
            'message'    => 'Predicción ejecutada correctamente.',
        ]);
    }

    public function churn(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $clienteModel = new ClienteModel();
        $clientes     = $clienteModel->churnRanking($empresaId, '', 100);

        // Llamar al microservicio Python para scoring actualizado
        if ($this->config['python_ai']['enabled']) {
            $datosClientes = array_map(fn($c) => [
                'id'              => $c['id'],
                'dias_sin_compra' => $c['dias_sin_compra'],
                'total_compras'   => $c['total_compras'],
                'ticket_promedio' => $c['ticket_promedio'],
                'monto_acumulado' => $c['monto_acumulado'],
            ], $clientes);

            $scores = $this->callPythonAI('/churn/score', [
                'clientes'   => $datosClientes,
                'empresa_id' => $empresaId,
            ]);

            if ($scores) {
                // Actualizar scores en BD
                $db = Database::getInstance();
                foreach ($scores['resultados'] ?? [] as $r) {
                    $db->prepare("UPDATE clientes SET churn_score = ?, churn_riesgo = ? WHERE id = ? AND empresa_id = ?")
                       ->execute([$r['score'], $r['riesgo'], $r['id'], $empresaId]);
                }
                // Recargar con scores actualizados
                $clientes = $clienteModel->churnRanking($empresaId, '', 100);
            }
        }

        $resumen = $clienteModel->resumenChurn($empresaId);

        $this->view('prediccion/churn', [
            'title'    => 'Predicción de Abandono de Clientes',
            'clientes' => $clientes,
            'resumen'  => $resumen,
        ]);
    }

    public function simulador(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $this->view('prediccion/simulador', [
            'title' => 'Simulador de Escenarios "What-If" con IA',
        ]);
    }

    public function simular(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $factors = [
            'precio'    => (float)$this->post('precio', 0),
            'marketing' => (float)$this->post('marketing', 0),
            'descuento' => (float)$this->post('descuento', 0),
            'ventas'    => (float)$this->post('ventas', 0),
        ];

        $serieVentas = $this->ventaModel->serieTemporalVentas($empresaId, 365);

        $resultado = $this->callPythonAI('/predict/simulate', [
            'data'       => $serieVentas,
            'factors'    => $factors,
            'empresa_id' => $empresaId,
        ]);

        if (!$resultado) {
            $resultado = $this->_fallbackSimular($serieVentas, $factors);
        }

        $this->json([
            'success' => true,
            'data'    => $resultado
        ]);
    }

    private function _fallbackSimular(array $data, array $factors): array
    {
        $precio_pct = $factors['precio'];
        $marketing_pct = $factors['marketing'];
        $descuento_pct = $factors['descuento'];
        $ventas_pct = $factors['ventas'];

        $mult_ventas = (1 + ($precio_pct / 100) * 0.6) 
                    * (1 + ($marketing_pct / 100) * 0.35) 
                    * (1 + ($descuento_pct / 100) * -0.1) 
                    * (1 + ($ventas_pct / 100) * 0.25);

        $tot_ventas = 0;
        foreach ($data as $d) {
            $tot_ventas += floatval($d['y']);
        }
        $media = count($data) > 0 ? ($tot_ventas / count($data)) : 10000;

        $hoy = new DateTime();
        $diario_base = [];
        $diario_simulada = [];

        $ventas_base = 0;
        $ventas_sim = 0;

        for ($i = 1; $i <= 30; $i++) {
            $fecha = (clone $hoy)->modify("+$i days")->format('Y-m-d');
            $val_base = $media * (1 + sin($i / 3) * 0.1);
            $val_sim = $val_base * $mult_ventas;

            $diario_base[] = [
                'ds' => $fecha,
                'yhat' => round($val_base, 2),
                'yhat_lower' => round($val_base * 0.85, 2),
                'yhat_upper' => round($val_base * 1.15, 2)
            ];

            $diario_simulada[] = [
                'ds' => $fecha,
                'yhat' => round($val_sim, 2),
                'yhat_lower' => round($val_sim * 0.85, 2),
                'yhat_upper' => round($val_sim * 1.15, 2)
            ];

            $ventas_base += $val_base;
            $ventas_sim += $val_sim;
        }

        $margen_base = 0.45;
        $margen_sim = $margen_base + ($precio_pct / 100) * 0.5 - ($descuento_pct / 100) * 0.8;
        $margen_sim = max(0.1, min(0.9, $margen_sim));

        return [
            'ventas_base' => round($ventas_base, 2),
            'ventas_simulada' => round($ventas_sim, 2),
            'utilidad_base' => round($ventas_base * $margen_base, 2),
            'utilidad_simulada' => round($ventas_sim * $margen_sim, 2),
            'margen_base_pct' => round($margen_base * 100, 1),
            'margen_simulada_pct' => round($margen_sim * 100, 1),
            'diario_base' => $diario_base,
            'diario_simulada' => $diario_simulada
        ];
    }
}
