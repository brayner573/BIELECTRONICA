<?php
/**
 * FAXEL BI — Configuración General de la Aplicación
 */
return [
    'name'        => 'FAXEL BI',
    'version'     => '1.0.0',
    'url'         => (function() {
        $protocol = (
            (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1)) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        ) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $basePath = '/SISTEMA_FAXEL/public'; // Fallback por defecto
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
            $dir = dirname($script);
            $dir = str_replace('\\', '/', $dir);
            if ($dir === '/' || $dir === '.') {
                $basePath = '';
            } else {
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                // Si la ruta del script contiene '/public' pero la URL visitada (URI) no,
                // significa que Apache está haciendo una redirección interna invisible desde el root
                if (str_contains($dir, '/public') && !str_contains($uri, '/public')) {
                    $basePath = str_replace('/public', '', $dir);
                    if ($basePath === '/') $basePath = '';
                } else {
                    $basePath = rtrim($dir, '/');
                }
            }
        }
        return $protocol . '://' . $host . $basePath;
    })(),
    'debug'       => true,
    'timezone'    => 'America/Lima',
    'locale'      => 'es_PE',
    'currency'    => 'PEN',
    'currency_sym'=> 'S/',

    // Python AI Microservice
    'python_ai' => [
        'base_url' => 'http://localhost:5000',
        'timeout'  => 2,
        'enabled'  => (isset($_SERVER['HTTP_HOST']) && (
            str_contains($_SERVER['HTTP_HOST'], 'localhost') || 
            str_contains($_SERVER['HTTP_HOST'], '127.0.0.1') ||
            str_contains($_SERVER['HTTP_HOST'], '192.168.') ||
            str_contains($_SERVER['HTTP_HOST'], '10.')
        )) || php_sapi_name() === 'cli',
    ],

    // LLM Configuration (opcional)
    'llm' => [
        'provider'   => 'local',   // 'openai' | 'ollama' | 'local'
        'model'      => 'llama2',
        'api_key'    => '',
        'base_url'   => 'http://localhost:11434',
    ],

    // Exportación
    'reports_path' => dirname(__DIR__) . '/reports/',
    'uploads_path' => dirname(__DIR__) . '/uploads/',

    // Paginación
    'per_page' => 25,

    // Sesión
    'session_lifetime' => 7200, // 2 horas en segundos

    // Meta
    'meta' => [
        'ventas_mes' => 1000000.00,  // Meta mensual de ventas
    ],

    // Colores del tema para gráficos
    'chart_colors' => [
        '#E53E3E', '#9B2C2C', '#C53030', '#F59E0B',
        '#10B981', '#475569', '#3182CE', '#E2E8F0'
    ],
];
