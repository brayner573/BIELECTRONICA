<?php
/**
 * FAXEL BI — Router MVC Manual
 */
class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'handler' => $handler,
            'regex'   => $this->pathToRegex($path),
        ];
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $this->basePath . $pattern . '/?$#';
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['regex'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                [$controllerClass, $action] = $route['handler'];

                if (!class_exists($controllerClass)) {
                    $this->abort(500, "Controlador $controllerClass no encontrado.");
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $action)) {
                    $this->abort(500, "Acción $action no encontrada en $controllerClass.");
                }

                $controller->$action($params);
                return;
            }
        }

        $this->abort(404, 'Ruta no encontrada.');
    }

    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        if ($code === 404) {
            include dirname(__DIR__) . '/app/views/errors/404.php';
        } else {
            echo "<h1>Error $code</h1><p>" . htmlspecialchars($message) . "</p>";
        }
        exit;
    }
}
