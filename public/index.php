<?php
/**
 * FAXEL BI — Front Controller
 * Punto de entrada único de la aplicación
 */

define('ROOT',    dirname(__DIR__));
define('APP',     ROOT . '/app');
define('CORE',    ROOT . '/core');
define('CONFIG',  ROOT . '/config');

// ── Timezone ────────────────────────────────────────────
date_default_timezone_set('America/Lima');

// ── Autoloader ──────────────────────────────────────────
spl_autoload_register(function(string $class): void {
    $paths = [
        CORE,
        APP . '/controllers',
        APP . '/models',
        APP . '/services',
    ];
    foreach ($paths as $path) {
        $file = "$path/{$class}.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Headers de seguridad ─────────────────────────────────
Security::setSecureHeaders();

// ── Manejo de errores ────────────────────────────────────
$appConfig = require CONFIG . '/app.php';

if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    set_exception_handler(function(Throwable $e) {
        Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        http_response_code(500);
        include ROOT . '/app/views/errors/500.php';
        exit;
    });
}

// ── Iniciar sesión ───────────────────────────────────────
Session::start();

// ── Enrutamiento ─────────────────────────────────────────
$basePath = '/SISTEMA_FAXEL/public'; // Fallback por defecto
if (isset($_SERVER['SCRIPT_NAME'])) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $dir = dirname($script);
    $dir = str_replace('\\', '/', $dir);
    if ($dir === '/' || $dir === '.') {
        $basePath = '';
    } else {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Si la URL física contiene '/public' pero la URL del navegador no,
        // es por la reescritura invisible desde el .htaccess de la raíz.
        if (str_contains($dir, '/public') && !str_contains($uri, '/public')) {
            $basePath = str_replace('/public', '', $dir);
            if ($basePath === '/') $basePath = '';
        } else {
            $basePath = rtrim($dir, '/');
        }
    }
}

$router = new Router($basePath);

require ROOT . '/routes/web.php';

$router->dispatch();
