<?php
// @since  2026-04-24 — CSRF token generation and validation (hash_equals, 64-byte hex)
declare(strict_types=1);

final class Csrf
{
    private const TOKEN_SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::TOKEN_SESSION_KEY])) {
            $_SESSION[self::TOKEN_SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::TOKEN_SESSION_KEY];
    }

    public static function regenerate(): string
    {
        $_SESSION[self::TOKEN_SESSION_KEY] = bin2hex(random_bytes(32));
        return (string) $_SESSION[self::TOKEN_SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals(self::token(), $token);
    }
}
