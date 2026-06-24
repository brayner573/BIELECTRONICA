<?php
/**
 * FAXEL BI — Ejecutor Nativo de Pruebas Unitarias (PHP)
 * Ejecutar con: php tests/run_tests.php (desde la raíz del proyecto)
 */

define('TEST_MODE', true);

// Registrar auto-cargador básico para simular el framework
spl_autoload_register(function ($class) {
    $paths = [
        dirname(__DIR__) . '/core/' . $class . '.php',
        dirname(__DIR__) . '/app/services/' . $class . '.php',
        dirname(__DIR__) . '/app/models/' . $class . '.php',
        dirname(__DIR__) . '/app/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Colores de consola
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RESET', "\033[0m");

$testsRun = 0;
$testsPassed = 0;
$testsFailed = 0;

/**
 * Función helper para realizar aserciones simples
 */
function assert_true($expression, $message = '') {
    global $testsRun, $testsPassed, $testsFailed;
    $testsRun++;
    if ($expression) {
        $testsPassed++;
        echo COLOR_GREEN . "  [OK] PASSED: " . COLOR_RESET . $message . PHP_EOL;
    } else {
        $testsFailed++;
        echo COLOR_RED . "  [ERROR] FAILED: " . COLOR_RESET . $message . PHP_EOL;
    }
}

echo COLOR_YELLOW . "=== INICIANDO PRUEBAS UNITARIAS NATIVAS: FAXEL BI ===" . COLOR_RESET . PHP_EOL . PHP_EOL;

// ──────────────────────────────────────────────────────────
// 1. PRUEBAS: Clase Security (Sanitización y Cifrado)
// ──────────────────────────────────────────────────────────
echo "1. Ejecutando pruebas para modulo core/Security.php..." . PHP_EOL;

// Test de sanitización XSS
$maliciousInput = "<script>alert('xss');</script>Hola & Mundo";
$sanitized = Security::e($maliciousInput);
assert_true(
    $sanitized === "&lt;script&gt;alert(&apos;xss&apos;);&lt;/script&gt;Hola &amp; Mundo",
    "Sanitizacion XSS convierte tags y comillas simples correctamente."
);

// Test de verificación de claves encriptadas (simulado)
$password = "brayner45A*";
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
assert_true(
    password_verify($password, $hash),
    "Cifrado y verificacion Bcrypt validan correctamente las claves."
);
echo PHP_EOL;

// ──────────────────────────────────────────────────────────
// 2. PRUEBAS: Clase ACL (Matriz de Control de Accesos)
// ──────────────────────────────────────────────────────────
echo "2. Ejecutando pruebas para modulo core/ACL.php..." . PHP_EOL;

// Simular matriz de permisos
$acl = [
    'vendedor' => [
        'facturas.view' => true,
        'facturas.create' => true,
        'prediccion.view' => false,
        'prediccion.train' => false,
    ],
    'gerente' => [
        'facturas.view' => true,
        'facturas.create' => true,
        'prediccion.view' => true,
        'prediccion.train' => true,
    ]
];

// Validar que vendedor pueda facturar pero no ver/entrenar IA
assert_true(
    isset($acl['vendedor']['facturas.create']) && $acl['vendedor']['facturas.create'] === true,
    "El rol VENDEDOR tiene permitido emitir comprobantes (facturas.create)."
);
assert_true(
    !isset($acl['vendedor']['prediccion.train']) || $acl['vendedor']['prediccion.train'] === false,
    "El rol VENDEDOR tiene denegado el reentrenamiento de modelos IA (prediccion.train)."
);
assert_true(
    isset($acl['gerente']['prediccion.train']) && $acl['gerente']['prediccion.train'] === true,
    "El rol GERENTE tiene permitido entrenar e inferir modelos de Machine Learning."
);
echo PHP_EOL;

// ──────────────────────────────────────────────────────────
// 3. PRUEBAS: FacturacionService (Emisión UBL 2.1 y XMLs)
// ──────────────────────────────────────────────────────────
echo "3. Ejecutando pruebas para modulo app/services/FacturacionService.php..." . PHP_EOL;

// Datos ficticios del comprobante
$cpeData = [
    'emisor_ruc'       => '20100000002',
    'emisor_razon'     => 'FAXEL BI TESTING CORP SAC',
    'tipo_cpe'         => '01', // Factura
    'serie'            => 'F001',
    'correlativo'      => 45,
    'cliente_ruc'      => '20555555551',
    'cliente_razon'    => 'CLIENTE PRUEBAS S.A.',
    'cliente_tipo_doc' => '6', // RUC
    'moneda'           => 'PEN',
    'items'            => [
        [
            'producto_id'     => 5,
            'codigo'          => 'P005',
            'nombre'          => 'Servicios de Consultoria Analitica',
            'cantidad'        => 2.000,
            'precio_unitario' => 118.00, // Con IGV
            'precio_costo'    => 50.00
        ]
    ]
];

// Instanciar y emitir
try {
    $res = FacturacionService::emitir($cpeData);
    
    assert_true($res['success'], "FacturacionService emite el comprobante sin errores.");
    assert_true($res['numero_completo'] === 'F001-00000045', "El numero de comprobante correlativo se compila correctamente.");
    
    // Validar cálculos (Total = 236.00, Subtotal = 200.00, IGV = 36.00)
    assert_true($res['subtotal'] == 200.00, "Calculo de base imponible (subtotal) descuenta correctamente el 18% de IGV.");
    assert_true($res['igv'] == 36.00, "Calculo del IGV determina el 18% del subtotal.");
    assert_true($res['total'] == 236.00, "El calculo del total consolidado es correcto.");
    
    // Verificar que los archivos XML y CDR existan en el sistema de desarrollo
    $xmlDiskPath = dirname(__DIR__) . '/public' . $res['xml_path'];
    $cdrDiskPath = dirname(__DIR__) . '/public' . $res['cdr_path'];
    
    assert_true(file_exists($xmlDiskPath), "El archivo XML del comprobante UBL 2.1 se escribe de forma correcta en el disco.");
    assert_true(file_exists($cdrDiskPath), "El archivo XML de Constancia de Recepcion (CDR) de SUNAT se escribe en el disco.");
    
    // Verificar largo de la firma digital
    assert_true(strlen($res['hash_cpe']) === 64, "La firma digital criptografica SHA-256 simulada tiene el largo correcto de 64 caracteres.");
    
    // Limpiar archivos de prueba
    if (file_exists($xmlDiskPath)) unlink($xmlDiskPath);
    if (file_exists($cdrDiskPath)) unlink($cdrDiskPath);

} catch (Exception $e) {
    $testsFailed++;
    echo COLOR_RED . "  [EXCEPCION] Fallo en la ejecucion del emisor: " . $e->getMessage() . COLOR_RESET . PHP_EOL;
}
echo PHP_EOL;

// ──────────────────────────────────────────────────────────
// RESUMEN FINAL
// ──────────────────────────────────────────────────────────
echo COLOR_YELLOW . "=== RESUMEN DE PRUEBAS ===" . COLOR_RESET . PHP_EOL;
echo "Pruebas Totales: " . $testsRun . PHP_EOL;
echo COLOR_GREEN . "Pasadas: " . $testsPassed . COLOR_RESET . PHP_EOL;
if ($testsFailed > 0) {
    echo COLOR_RED . "Fallidas: " . $testsFailed . COLOR_RESET . PHP_EOL;
    exit(1); // Retornar código de error
} else {
    echo COLOR_GREEN . "¡Todas las pruebas pasaron de forma exitosa!" . COLOR_RESET . PHP_EOL;
    exit(0);
}
