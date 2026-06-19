<?php
/**
 * FAXEL BI — Controlador de Alertas (Módulo 5) SaaS Multiempresa
 */
class AlertaController extends Controller
{
    private AlertaModel $alertaModel;

    public function __construct()
    {
        parent::__construct();
        $this->alertaModel = new AlertaModel();
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        $nivel  = $this->get('nivel', '');
        $estado = $this->get('estado', '');

        $db = Database::getInstance();
        $params_q = [$empresaId];
        $where    = ["a.empresa_id = ?"];

        if ($nivel)  { $where[] = "a.nivel = ?";   $params_q[] = $nivel; }
        if ($estado) { $where[] = "a.estado = ?";  $params_q[] = $estado; }

        $whereStr = 'WHERE ' . implode(' AND ', $where);

        $alertas = $db->prepare("
            SELECT a.*, u.nombre AS resuelto_por
            FROM alertas a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            $whereStr
            ORDER BY
                FIELD(a.estado, 'nueva', 'revisada', 'resuelta'),
                FIELD(a.nivel, 'danger', 'warning', 'info', 'success'),
                a.created_at DESC
            LIMIT 100
        ");
        $alertas->execute($params_q);
        $alertas = $alertas->fetchAll();

        $stats = $this->alertaModel->contarPorEstado($empresaId);

        $this->view('alertas/index', [
            'title'   => 'Centro de Alertas',
            'alertas' => $alertas,
            'stats'   => $stats,
            'nivel'   => $nivel,
            'estado'  => $estado,
        ]);
    }

    public function resolver(array $params = []): void
    {
        $this->requireAuth();
        $this->verifyCSRF();
        $empresaId = $this->getEmpresaId();

        $id   = (int)($params['id'] ?? 0);
        $user = Session::get('user');

        if ($this->alertaModel->marcarResuelta($id, $empresaId, $user['id'])) {
            Logger::audit('ALERTA_RESUELTA', 'alertas', $id);
            $this->json(['success' => true, 'message' => 'Alerta marcada como resuelta.']);
        } else {
            $this->json(['success' => false, 'message' => 'No se pudo actualizar.'], 400);
        }
    }

    public function revisar(array $params = []): void
    {
        $this->requireAuth();
        $this->verifyCSRF();
        $empresaId = $this->getEmpresaId();

        $id = (int)($params['id'] ?? 0);

        if ($this->alertaModel->marcarRevisada($id, $empresaId)) {
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false], 400);
        }
    }

    public function stream(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();
        $db = Database::getInstance();

        // Si es consulta inicial (init) o de polling (ajax/last_id)
        if (isset($_GET['init']) || isset($_GET['last_id'])) {
            if (isset($_GET['init'])) {
                $stmtMax = $db->prepare("SELECT MAX(id) as max_id FROM alertas WHERE empresa_id = ?");
                $stmtMax->execute([$empresaId]);
                $rowMax = $stmtMax->fetch();
                $this->json(['last_id' => (int)($rowMax['max_id'] ?? 0)]);
                return;
            }

            $lastId = (int)$_GET['last_id'];
            $stmt = $db->prepare("
                SELECT id, tipo, nivel, titulo, mensaje, created_at 
                FROM alertas 
                WHERE empresa_id = ? AND id > ? AND estado = 'nueva'
                ORDER BY id ASC
            ");
            $stmt->execute([$empresaId, $lastId]);
            $nuevas = $stmt->fetchAll();
            $this->json(['nuevas' => $nuevas]);
            return;
        }

        // Evitar bloqueo de sesiones concurrentes
        session_write_close();

        // Desactivar límite de tiempo de ejecución de PHP
        set_time_limit(0);

        // Limpiar buffers de salida previos
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Configurar cabeceras HTTP para Server-Sent Events (SSE)
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Obtener el ID de la alerta más reciente al momento de conectar para no repetir alertas anteriores
        $stmtMax = $db->prepare("SELECT MAX(id) as max_id FROM alertas WHERE empresa_id = ?");
        $stmtMax->execute([$empresaId]);
        $rowMax = $stmtMax->fetch();
        $lastCheckedId = (int)($rowMax['max_id'] ?? 0);

        // Bucle infinito de streaming
        while (true) {
            if (connection_aborted()) {
                break;
            }

            // Consultar nuevas alertas en estado 'nueva'
            $stmt = $db->prepare("
                SELECT id, tipo, nivel, titulo, mensaje, created_at 
                FROM alertas 
                WHERE empresa_id = ? AND id > ? AND estado = 'nueva'
                ORDER BY id ASC
            ");
            $stmt->execute([$empresaId, $lastCheckedId]);
            $nuevas = $stmt->fetchAll();

            if (!empty($nuevas)) {
                foreach ($nuevas as $alerta) {
                    $lastCheckedId = max($lastCheckedId, (int)$alerta['id']);
                    echo "data: " . json_encode($alerta, JSON_UNESCAPED_UNICODE) . "\n\n";
                }
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } else {
                // Keep-alive para evitar desconexiones por inactividad
                echo ": keepalive\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            // Esperar 3 segundos para el próximo chequeo
            sleep(3);
        }
        exit;
    }


    public function generar(array $params = []): void
    {
        $this->requireAuth();
        $this->requirePermission('alertas.manage');
        $empresaId = $this->getEmpresaId();

        $creadas = $this->alertaModel->generarAlertasAutomaticas($empresaId);
        Logger::info("Alertas automáticas generadas para empresa $empresaId: $creadas");

        $this->json([
            'success' => true,
            'creadas' => $creadas,
            'message' => "$creadas alertas nuevas generadas.",
        ]);
    }
}
