<?php
/**
 * FAXEL BI — Conexión Base de Datos (Singleton PDO)
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require dirname(__DIR__) . '/config/database.php';

            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";

            self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
        }
        return self::$instance;
    }

    // Evitar clonado
    private function __clone() {}
}
