<?php
/**
 * FAXEL BI — Controller Base
 */
abstract class Controller
{
    protected array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/config/app.php';
        Session::start();
    }

    /* ── Renderizado de vistas ────────────────────────────── */

    protected function view(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        // Extraer variables para la vista
        extract($data);
        $config  = $this->config;
        $user    = Session::get('user');

        // Capturar contenido de la vista
        ob_start();
        require dirname(__DIR__) . "/app/views/{$viewPath}.php";
        $content = ob_get_clean();

        // Renderizar layout
        require dirname(__DIR__) . "/app/views/layouts/{$layout}.php";
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* ── Redirecciones ────────────────────────────────────── */

    protected function redirect(string $path): void
    {
        $base = rtrim($this->config['url'], '/');
        header("Location: {$base}{$path}");
        exit;
    }

    /* ── Seguridad ────────────────────────────────────────── */

    protected function requireAuth(): void
    {
        if (!Session::get('user')) {
            $this->redirect('/login');
        }
    }

    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();
        $user = Session::get('user');
        if (!ACL::hasPermission($user['rol'], $permission)) {
            http_response_code(403);
            require dirname(__DIR__) . '/app/views/errors/403.php';
            exit;
        }
    }

    protected function getEmpresaId(): int
    {
        $this->requireAuth();
        return (int)(Session::get('user')['empresa_id'] ?? 1);
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireAuth();
        $user = Session::get('user');
        if (!in_array($user['rol'], $roles)) {
            http_response_code(403);
            require dirname(__DIR__) . '/app/views/errors/403.php';
            exit;
        }
    }

    protected function verifyCSRF(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!Security::verifyCSRF($token)) {
            http_response_code(403);
            die(json_encode(['error' => 'CSRF token inválido']));
        }
    }

    /* ── Input helpers ────────────────────────────────────── */

    protected function input(string $key, mixed $default = null): mixed
    {
        return Security::sanitize($_POST[$key] ?? $_GET[$key] ?? $default);
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return Security::sanitize($_POST[$key] ?? $default);
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return Security::sanitize($_GET[$key] ?? $default);
    }

    /* ── Python AI helper ─────────────────────────────────── */

    protected function callPythonAI(string $endpoint, array $payload = [], string $method = 'POST'): ?array
    {
        if (!($this->config['python_ai']['enabled'] ?? true)) {
            return null;
        }

        // Si sabemos que el microservicio está offline, no intentamos llamarlo para evitar lentitud
        $offlineUntil = Session::get('python_ai_offline_until', 0);
        if ($offlineUntil > time()) {
            return null;
        }

        $baseUrl = $this->config['python_ai']['base_url'];
        $timeout = $this->config['python_ai']['timeout'];

        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $ch  = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 2, // Conexión rápida de 2 segundos
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            Logger::error("Python AI error: $endpoint | HTTP $httpCode | cURL Error: $curlError");
            
            // Si la conexión falló completamente (ej. host apagado), guardamos estado offline por 30 segundos
            if ($response === false) {
                Session::set('python_ai_offline_until', time() + 30);
            }
            return null;
        }

        return json_decode($response, true);
    }
}
