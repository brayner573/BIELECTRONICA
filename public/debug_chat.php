<?php
/**
 * FAXEL BI — Script de Diagnóstico del Asistente IA
 */

define('ROOT',    dirname(__DIR__));
define('CORE',    ROOT . '/core');
define('CONFIG',  ROOT . '/config');
define('APP',     ROOT . '/app');

// Mostrar todos los errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Registrar Autoloader
spl_autoload_register(function(string $class): void {
    $paths = [CORE, APP . '/controllers', APP . '/models', APP . '/services'];
    foreach ($paths as $path) {
        $file = "$path/{$class}.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔍 Diagnóstico de Conexión del Asistente IA</h2>";

// 1. Detectar variables de servidor
echo "<h3>1. Servidor y Entorno</h3>";
$host = $_SERVER['HTTP_HOST'] ?? 'No disponible';
echo "• HTTP_HOST: <b>$host</b><br>";
$isLocal = (
    (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false || strpos($_SERVER['HTTP_HOST'], '10.') !== false)) ||
    php_sapi_name() === 'cli'
);
echo "• ¿Es entorno Local detectado?: <b>" . ($isLocal ? "SÍ (Usa base de datos Local)" : "NO (Usa base de datos de cPanel/Producción)") . "</b><br>";

// 2. Probar conexión a la Base de Datos
echo "<h3>2. Base de Datos</h3>";
try {
    $dbConfig = require CONFIG . '/database.php';
    echo "• Servidor configurado: <b>{$dbConfig['host']}</b><br>";
    echo "• Base de datos configurada: <b>{$dbConfig['dbname']}</b><br>";
    echo "• Usuario configurado: <b>{$dbConfig['username']}</b><br>";
    
    $db = Database::getInstance();
    echo "• <b>Conexión exitosa a la Base de Datos!</b> 🟢<br>";
    
    // Probar tabla ventas
    $stmt = $db->query("SELECT COUNT(*) FROM ventas");
    $cnt = $stmt->fetchColumn();
    echo "• Tabla 'ventas': <b>SÍ existe</b> ($cnt registros)<br>";
    
    // Probar tabla chat_logs
    $stmt = $db->query("SELECT COUNT(*) FROM chat_logs");
    $cntLogs = $stmt->fetchColumn();
    echo "• Tabla 'chat_logs': <b>SÍ existe</b> ($cntLogs registros)<br>";
    
} catch (Exception $e) {
    echo "• ❌ <b>Error de Base de Datos:</b> <span style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// 3. Probar configuración de Python AI
echo "<h3>3. Microservicio Python AI</h3>";
$appConfig = require CONFIG . '/app.php';
$pyConfig = $appConfig['python_ai'];
echo "• Habilitado en configuración: <b>" . ($pyConfig['enabled'] ? 'SÍ' : 'NO') . "</b><br>";
echo "• URL base: <b>{$pyConfig['base_url']}</b><br>";

if ($pyConfig['enabled']) {
    echo "• Probando conexión con el microservicio en {$pyConfig['base_url']}/health...<br>";
    $ch = curl_init(rtrim($pyConfig['base_url'], '/') . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response !== false && $httpCode === 200) {
        echo "• <b>Microservicio Python responde correctamente!</b> 🟢<br>";
        echo "• Respuesta: <code>" . htmlspecialchars($response) . "</code><br>";
    } else {
        echo "• ❌ <b>Microservicio Python no responde:</b> Code $httpCode | Error: $curlError<br>";
        echo "• <i>Nota: Si estás en cPanel, esto es normal. La configuración debería apagar automáticamente el microservicio si no es local.</i><br>";
    }
} else {
    echo "• <i>La llamada a la IA de Python está deshabilitada. El chat responderá usando el Analizador SQL local.</i> 🟢<br>";
}
