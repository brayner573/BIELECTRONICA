<?php
/**
 * FAXEL BI — Configuración de Base de Datos
 */
$isLocal = (
    (isset($_SERVER['HTTP_HOST']) && (
        str_contains($_SERVER['HTTP_HOST'], 'localhost') || 
        str_contains($_SERVER['HTTP_HOST'], '127.0.0.1') ||
        str_contains($_SERVER['HTTP_HOST'], '192.168.') ||
        str_contains($_SERVER['HTTP_HOST'], '10.')
    )) ||
    php_sapi_name() === 'cli'
);

if ($isLocal) {
    return [
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => 'faxel_bi',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
        ]
    ];
} else {
    return [
        'host'     => 'localhost', // Servidor de base de datos cPanel (Obligatorio localhost en producción)
        'port'     => 3306,
        'dbname'   => 'iaws_faxel_bi',
        'username' => 'iaws_brayner1',
        'password' => 'brayner45A*',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
        ]
    ];
}
