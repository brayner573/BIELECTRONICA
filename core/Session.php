<?php
/**
 * FAXEL BI — Gestión de Sesiones Seguras
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $appCfg = require dirname(__DIR__) . '/config/app.php';

            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', $appCfg['session_lifetime']);

            session_name('FAXEL_SESSION');
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function setUser(array $userData): void
    {
        self::regenerate();
        self::set('user', [
            'id'        => $userData['id'],
            'nombre'    => $userData['nombre'],
            'apellido'  => $userData['apellido'],
            'email'     => $userData['email'],
            'rol'       => $userData['rol'],
            'avatar'    => $userData['avatar'] ?? null,
            'login_at'  => time(),
        ]);
    }

    public static function isLoggedIn(): bool
    {
        return self::has('user');
    }
}
