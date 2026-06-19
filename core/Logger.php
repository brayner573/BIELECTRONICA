<?php
/**
 * FAXEL BI — Logger (archivo + BD)
 */
class Logger
{
    private static string $logDir = '';

    private static function getLogDir(): string
    {
        if (!self::$logDir) {
            self::$logDir = dirname(__DIR__) . '/logs/';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    public static function write(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $user      = Session::get('user');
        $userId    = $user['id'] ?? 0;
        $ip        = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $ctx       = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        $line = "[$timestamp][$level][USER:$userId][IP:$ip] $message$ctx" . PHP_EOL;

        $file = self::getLogDir() . date('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function audit(string $accion, string $tabla = '', int $registroId = 0, array $before = [], array $after = []): void
    {
        self::write('AUDIT', "$accion | Tabla:$tabla | ID:$registroId");

        try {
            $db   = Database::getInstance();
            $user = Session::get('user');
            $stmt = $db->prepare("
                INSERT INTO audit_log (usuario_id, accion, tabla, registro_id, datos_antes, datos_desp, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'] ?? null,
                $accion,
                $tabla ?: null,
                $registroId ?: null,
                !empty($before) ? json_encode($before) : null,
                !empty($after)  ? json_encode($after)  : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Exception $e) {
            // No bloquear si falla el log en BD
        }
    }
}
