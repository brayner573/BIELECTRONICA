<?php
/**
 * FAXEL BI — Controlador del Chat IA Empresarial (Módulo 6) SaaS Multiempresa
 */
class ChatController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $user    = Session::get('user');
        $db      = Database::getInstance();
        $sesion  = Session::get('chat_session_id', bin2hex(random_bytes(16)));
        Session::set('chat_session_id', $sesion);

        // Historial de la sesión actual
        $historial = $db->prepare("
            SELECT rol, mensaje, tiene_grafico, grafico_data, created_at
            FROM chat_logs
            WHERE usuario_id = ? AND sesion_id = ? AND empresa_id = ?
            ORDER BY created_at
            LIMIT 50
        ");
        $historial->execute([$user['id'], $sesion, $empresaId]);
        $mensajes = $historial->fetchAll();

        $this->view('chat/index', [
            'title'    => 'Asistente IA Empresarial',
            'mensajes' => $mensajes,
            'sesionId' => $sesion,
        ]);
    }

    public function enviar(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('chat.use');
        $this->verifyCSRF();

        $user    = Session::get('user');
        $empresaId = $this->getEmpresaId();
        $db      = Database::getInstance();
        $pregunta = trim($this->post('mensaje', ''));
        $sesion   = Session::get('chat_session_id', bin2hex(random_bytes(16)));

        if (!$pregunta) {
            $this->json(['error' => 'Mensaje vacío'], 400);
            return;
        }

        // Regenerar alertas automáticas si la pregunta pide alertas o anuncios
        $p_lower = mb_strtolower($pregunta);
        if (str_contains($p_lower, 'alerta') || str_contains($p_lower, 'anuncio') || str_contains($p_lower, 'aviso') || str_contains($p_lower, 'notifica') || str_contains($p_lower, 'problem')) {
            $alertaModel = new AlertaModel();
            $alertaModel->generarAlertasAutomaticas($empresaId);
        }

        // Guardar mensaje del usuario
        $db->prepare("
            INSERT INTO chat_logs (empresa_id, usuario_id, sesion_id, rol, mensaje, modelo_ia)
            VALUES (?, ?, ?, 'user', ?, 'local_sql')
        ")->execute([$empresaId, $user['id'], $sesion, $pregunta]);

        // Obtener historial para contexto
        $historial = $db->prepare("
            SELECT rol, mensaje FROM chat_logs
            WHERE usuario_id = ? AND sesion_id = ? AND empresa_id = ?
            ORDER BY created_at DESC LIMIT 10
        ");
        $historial->execute([$user['id'], $sesion, $empresaId]);
        $contexto = array_reverse($historial->fetchAll());

        // ── Intentar microservicio Python ───────────────────
        $inicio   = microtime(true);
        $respuesta = null;

        if ($this->config['python_ai']['enabled']) {
            $respuesta = $this->callPythonAI('/chat/query', [
                'pregunta'   => $pregunta,
                'historial'  => $contexto,
                'empresa_id' => $empresaId,
                'usuario'    => ['nombre' => $user['nombre'], 'rol' => $user['rol']],
            ]);
        }

        // ── Fallback: Analizador SQL Local ──────────────────
        if (!$respuesta) {
            $respuesta = $this->analizadorLocal($pregunta, $empresaId);
        }

        $tiempo = (int)((microtime(true) - $inicio) * 1000);

        // Guardar respuesta del asistente
        $db->prepare("
            INSERT INTO chat_logs (empresa_id, usuario_id, sesion_id, rol, mensaje, modelo_ia, tiene_grafico, grafico_data, sql_generado, tiempo_resp_ms)
            VALUES (?, ?, ?, 'assistant', ?, ?, ?, ?, ?, ?)
        ")->execute([
            $empresaId,
            $user['id'],
            $sesion,
            $respuesta['texto'] ?? 'No pude procesar tu consulta.',
            $respuesta['modelo'] ?? 'local_sql',
            !empty($respuesta['grafico']) ? 1 : 0,
            !empty($respuesta['grafico']) ? json_encode($respuesta['grafico']) : null,
            $respuesta['sql'] ?? null,
            $tiempo,
        ]);

        $this->json([
            'success'  => true,
            'respuesta'=> $respuesta['texto'] ?? '',
            'grafico'  => $respuesta['grafico'] ?? null,
            'tiempo'   => $tiempo,
        ]);
    }

    public function nuevaSesion(array $params = []): void
    {
        $this->requireAuth();
        Session::remove('chat_session_id');
        $this->redirect('/chat');
    }

    /**
     * Analizador SQL local — responde preguntas empresariales comunes de forma aislada
     */
    private function analizadorLocal(string $pregunta, int $empresaId): array
    {
        $p = mb_strtolower($pregunta);
        $db = Database::getInstance();

        // ── "¿Cuánto vendí este mes?" ──
        if (str_contains($p, 'vendí') || str_contains($p, 'venta') || str_contains($p, 'vendi')) {
            if (str_contains($p, 'mes')) {
                $stmt = $db->prepare("
                    SELECT SUM(total) FROM ventas
                    WHERE empresa_id = ? AND YEAR(fecha_venta)=YEAR(CURDATE()) AND MONTH(fecha_venta)=MONTH(CURDATE())
                    AND estado='completada'
                ");
                $stmt->execute([$empresaId]);
                $total = (float)$stmt->fetchColumn();

                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM ventas
                    WHERE empresa_id = ? AND YEAR(fecha_venta)=YEAR(CURDATE()) AND MONTH(fecha_venta)=MONTH(CURDATE())
                    AND estado='completada'
                ");
                $stmt->execute([$empresaId]);
                $num = (int)$stmt->fetchColumn();

                return [
                    'texto'  => "📊 **Ventas del mes en curso:**\n\n• **Total vendido:** S/ " . number_format($total,2) . "\n• **Número de ventas:** $num transacciones\n• **Ticket promedio:** S/ " . ($num > 0 ? number_format($total/$num,2) : '0.00') . "\n\nEl mes actual muestra " . ($total > 50000 ? "un desempeño positivo 🟢" : "oportunidad de mejora 🟡") . ".",
                    'modelo' => 'local_sql',
                    'sql'    => "SELECT SUM(total), COUNT(*) FROM ventas WHERE empresa_id=$empresaId AND YEAR=YEAR(CURDATE()) AND MONTH=MONTH(CURDATE())",
                ];
            }
            if (str_contains($p, 'hoy')) {
                $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM ventas WHERE empresa_id = ? AND DATE(fecha_venta)=CURDATE() AND estado='completada'");
                $stmt->execute([$empresaId]);
                $total = (float)$stmt->fetchColumn();
                return ['texto' => "📊 **Ventas de hoy:** S/ " . number_format($total,2), 'modelo' => 'local_sql'];
            }
        }

        // ── "¿Qué sucursal cayó más?" ──
        if (str_contains($p, 'sucursal') && (str_contains($p, 'cayó') || str_contains($p, 'bajo') || str_contains($p, 'cayeron'))) {
            $stmt = $db->prepare("
                SELECT s.nombre,
                    SUM(CASE WHEN v.fecha_venta >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) THEN v.total ELSE 0 END) AS mes_actual,
                    SUM(CASE WHEN v.fecha_venta BETWEEN DATE_SUB(CURDATE(),INTERVAL 60 DAY)
                             AND DATE_SUB(CURDATE(),INTERVAL 31 DAY) THEN v.total ELSE 0 END) AS mes_ant
                FROM sucursales s 
                LEFT JOIN ventas v ON v.sucursal_id=s.id AND v.estado='completada' AND v.empresa_id = ?
                WHERE s.empresa_id = ?
                GROUP BY s.id ORDER BY ((mes_actual-mes_ant)/NULLIF(mes_ant,0)) ASC LIMIT 1
            ");
            $stmt->execute([$empresaId, $empresaId]);
            $rows = $stmt->fetch();
            if ($rows) {
                $cambio = $rows['mes_ant'] > 0 ? round((($rows['mes_actual']-$rows['mes_ant'])/$rows['mes_ant'])*100,1) : 0;
                return ['texto' => "📉 La sucursal con mayor caída es **{$rows['nombre']}** con un cambio de **{$cambio}%** respecto al mes anterior.\n\n• Mes actual: S/ " . number_format($rows['mes_actual'],2) . "\n• Mes anterior: S/ " . number_format($rows['mes_ant'],2), 'modelo' => 'local_sql'];
            }
        }

        // ── "¿Qué producto genera más utilidad?" ──
        if (str_contains($p, 'producto') && (str_contains($p, 'utilidad') || str_contains($p, 'rentabl') || str_contains($p, 'gananci'))) {
            $stmt = $db->prepare("
                SELECT p.nombre, SUM(dv.utilidad_linea) AS utilidad, SUM(dv.subtotal) AS ingresos
                FROM detalle_venta dv
                JOIN productos p ON p.id=dv.producto_id
                JOIN ventas v ON v.id=dv.venta_id AND v.estado='completada' AND v.empresa_id = ?
                WHERE p.empresa_id = ?
                GROUP BY p.id ORDER BY utilidad DESC LIMIT 5
            ");
            $stmt->execute([$empresaId, $empresaId]);
            $rows = $stmt->fetchAll();
            $lista = "**Top 5 Productos más rentables:**\n\n";
            foreach ($rows as $i => $r) {
                $lista .= ($i+1) . ". **{$r['nombre']}** → S/ " . number_format($r['utilidad'],2) . " utilidad\n";
            }
            return ['texto' => "💰 $lista", 'modelo' => 'local_sql'];
        }

        // ── "¿Quiénes dejarán de comprar?" ──
        if (str_contains($p, 'abandon') || str_contains($p, 'churn') || (str_contains($p, 'dejar') && str_contains($p, 'comprar'))) {
            $stmt = $db->prepare("
                SELECT c.razon_social, c.churn_score, c.churn_riesgo, DATEDIFF(CURDATE(), c.ultima_compra) AS dias_inactivo
                FROM clientes c
                WHERE c.activo = 1 AND c.empresa_id = ? AND c.churn_riesgo='alto' 
                ORDER BY c.churn_score DESC 
                LIMIT 5
            ");
            $stmt->execute([$empresaId]);
            $rows = $stmt->fetchAll();
            $lista = "⚠️ **Clientes con alto riesgo de abandono:**\n\n";
            foreach ($rows as $r) {
                $lista .= "• **{$r['razon_social']}** — Score: {$r['churn_score']}/100 | {$r['dias_inactivo']} días inactivo\n";
            }
            return ['texto' => $lista, 'modelo' => 'local_sql'];
        }

        // ── "Resume mi negocio" / "resumen" ──
        if (str_contains($p, 'resume') || str_contains($p, 'resumen') || str_contains($p, 'negocio')) {
            $stmt = $db->prepare("
                SELECT
                    COALESCE((SELECT SUM(total) FROM ventas WHERE empresa_id=? AND MONTH(fecha_venta)=MONTH(CURDATE()) AND estado='completada'),0) AS ventas,
                    COALESCE((SELECT COUNT(DISTINCT cliente_id) FROM ventas WHERE empresa_id=? AND MONTH(fecha_venta)=MONTH(CURDATE()) AND estado='completada'),0) AS clientes,
                    COALESCE((SELECT AVG(margen_pct) FROM ventas WHERE empresa_id=? AND MONTH(fecha_venta)=MONTH(CURDATE()) AND estado='completada'),0) AS margen,
                    (SELECT COUNT(*) FROM alertas WHERE empresa_id=? AND estado='nueva') AS alertas
            ");
            $stmt->execute([$empresaId, $empresaId, $empresaId, $empresaId]);
            $r = $stmt->fetch();

            return [
                'texto' => "📋 **Resumen Ejecutivo — " . date('F Y') . ":**\n\n" .
                    "• **Ventas del mes:** S/ " . number_format($r['ventas'],2) . "\n" .
                    "• **Clientes activos:** {$r['clientes']}\n" .
                    "• **Margen promedio:** " . round($r['margen'],1) . "%\n" .
                    "• **Alertas nuevas:** {$r['alertas']}\n\n" .
                    ($r['ventas'] > 100000 ? "✅ El negocio muestra buen desempeño este mes." : "⚠️ Las ventas están por debajo de la expectativa. Revisa las alertas."),
                'modelo' => 'local_sql',
            ];
        }

        // ── "Anuncios" / "Alertas" ──
        if (str_contains($p, 'alerta') || str_contains($p, 'anuncio') || str_contains($p, 'aviso') || str_contains($p, 'notifica') || str_contains($p, 'problem')) {
            $stmt = $db->prepare("
                SELECT titulo, nivel, mensaje, estado
                FROM alertas
                WHERE empresa_id = ? AND estado != 'resuelta'
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$empresaId]);
            $rows = $stmt->fetchAll();

            if ($rows) {
                $lista = "📢 **Anuncios y Alertas de Ventas Activas:**\n\n";
                foreach ($rows as $r) {
                    $icon = ($r['nivel'] === 'danger') ? '🔴' : (($r['nivel'] === 'warning') ? '🟡' : 'ℹ️');
                    $lista .= "$icon **{$r['titulo']}**:\n{$r['mensaje']}\n\n";
                }
                return [
                    'texto'  => $lista,
                    'modelo' => 'local_sql',
                    'sql'    => "SELECT titulo, nivel, mensaje FROM alertas WHERE empresa_id=$empresaId AND estado!='resuelta' LIMIT 5",
                ];
            } else {
                return [
                    'texto'  => "✅ **Todo en orden:** No se detectaron alertas ni anuncios de ventas pendientes para tu empresa.",
                    'modelo' => 'local_sql',
                ];
            }
        }

        // ── Respuesta genérica ──
        return [
            'texto'  => "🤖 Hola! Puedo responder preguntas sobre:\n\n• **Ventas** (\"¿Cuánto vendí este mes?\", \"Ventas de hoy\")\n• **Sucursales** (\"¿Qué sucursal cayó más?\")\n• **Productos** (\"¿Qué producto genera más utilidad?\")\n• **Clientes** (\"¿Quiénes dejarán de comprar?\")\n• **Resumen** (\"Resume mi negocio\")\n\n¿Qué deseas consultar?",
            'modelo' => 'local_sql',
        ];
    }
}
