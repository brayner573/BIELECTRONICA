<?php
/**
 * FAXEL BI — Script de utilidad para corregir permisos de archivos en cPanel
 * Ubicado en la carpeta public/ para ejecutarse directamente
 * Directorios -> 755 | Archivos -> 644
 */

function fixPermissions($dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $filesFixed = 0;
    $dirsFixed = 0;

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        
        // Ignorar este mismo archivo
        if (basename($path) === 'fix_permissions.php') {
            continue;
        }

        if ($item->isDir()) {
            if (chmod($path, 0755)) {
                $dirsFixed++;
            }
        } else {
            if (chmod($path, 0644)) {
                $filesFixed++;
            }
        }
    }

    echo "<h2>FAXEL BI — Permisos Corregidos Exitosamente</h2>";
    echo "<p>Se han reestablecido los permisos correctos en todo el servidor:</p>";
    echo "<ul>";
    echo "<li><strong>Directorios corregidos a 755:</strong> $dirsFixed</li>";
    echo "<li><strong>Archivos corregidos a 644:</strong> $filesFixed</li>";
    echo "</ul>";
    echo "<p style='color:red; font-weight:bold;'>⚠️ POR SEGURIDAD: Elimina este archivo (public/fix_permissions.php) del servidor de inmediato.</p>";
}

// Ejecutar sobre el directorio raíz (padre de public/)
fixPermissions(dirname(__DIR__));
