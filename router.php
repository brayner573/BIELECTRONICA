<?php
/**
 * FAXEL BI — Router para el Servidor de Desarrollo de PHP
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Si el archivo existe bajo el DOCUMENT_ROOT, dejar que el servidor lo sirva directamente
if ($uri !== '/' && is_file($_SERVER['DOCUMENT_ROOT'] . $uri)) {
    return false;
}

// Si no, enrutar todo al controlador frontal
$_SERVER['SCRIPT_NAME'] = '/SISTEMA_FAXEL/public/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
require_once __DIR__ . '/public/index.php';
