<?php

namespace ESign;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . Helpers::e(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || !$token) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyOrFail(?string $token): void
    {
        if (!self::verify($token)) {
            http_response_code(419);
            die('คำขอไม่ถูกต้องหรือหมดอายุ กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง (CSRF token invalid)');
        }
    }
}
