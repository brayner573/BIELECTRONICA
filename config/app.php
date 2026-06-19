<?php
/**
 * FAXEL BI — Configuración General de la Aplicación
 */
return [
    'name'        => 'FAXEL BI',
    'version'     => '1.0.0',
    'url'         => 'http://localhost/SISTEMA_FAXEL/public',
    'debug'       => true,
    'timezone'    => 'America/Lima',
    'locale'      => 'es_PE',
    'currency'    => 'PEN',
    'currency_sym'=> 'S/',

    // Python AI Microservice
    'python_ai' => [
        'base_url' => 'http://localhost:5000',
        'timeout'  => 2,
        'enabled'  => true,
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
