<?php
/**
 * FAXEL BI — Seguridad: CSRF, XSS, Sanitización
 */
class Security
{
    /* ── CSRF ─────────────────────────────────────────────── */

    public static function generateCSRF(): string
    {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    public static function verifyCSRF(string $token): bool
    {
        $stored = Session::get('csrf_token', '');
        return hash_equals($stored, $token);
    }

    public static function csrfField(): string
    {
        $token = self::generateCSRF();
        return "<input type=\"hidden\" name=\"_csrf\" value=\"{$token}\">";
    }

    /* ── XSS ──────────────────────────────────────────────── */

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        if (is_string($value)) {
            return trim(strip_tags($value));
        }
        return $value;
    }

    /* ── Contraseñas ──────────────────────────────────────── */

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /* ── Validaciones ─────────────────────────────────────── */

    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    public static function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) $errors[] = 'Mínimo 8 caracteres.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Debe tener al menos una mayúscula.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Debe tener al menos un número.';
        return $errors;
    }

    /* ── Headers de seguridad ─────────────────────────────── */

    public static function setSecureHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
