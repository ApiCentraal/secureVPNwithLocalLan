<?php
// @since  2026-04-24 — login/session management with brute-force lockout (5 attempts / 300 s window)
declare(strict_types=1);

final class Auth
{
    private const USER_SESSION_KEY = 'userId';
    private const USERNAME_SESSION_KEY = 'username';
    private const LOGIN_ATTEMPTS_KEY = 'login_attempts';
    private const LOGIN_WINDOW_KEY = 'login_window_start';
    private const LOGIN_LOCK_UNTIL_KEY = 'login_lock_until';

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 300;
    private const LOCKOUT_SECONDS = 300;

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION[self::USER_SESSION_KEY]);
    }

    public static function requireLoginRedirect(string $location = './index.php'): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . $location);
            exit();
        }
    }

    public static function requireLoginJson(): void
    {
        if (self::isLoggedIn()) {
            return;
        }

        self::jsonResponse(
            ['success' => false, 'error' => 'Authentication required.'],
            401
        );
    }

    public static function currentUsername(): string
    {
        if (!empty($_SESSION[self::USERNAME_SESSION_KEY])) {
            return (string) $_SESSION[self::USERNAME_SESSION_KEY];
        }

        return self::configuredUsername();
    }

    public static function attemptLogin(string $username, string $password, ?string &$errorCode = null): bool
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            $errorCode = 'missing';
            return false;
        }

        if (self::isLockedOut()) {
            $errorCode = 'locked';
            return false;
        }

        self::resetLoginWindowIfNeeded();

        $isUsernameValid = hash_equals(self::configuredUsername(), $username);
        $isPasswordValid = self::verifyPassword($password);

        if (!$isUsernameValid || !$isPasswordValid) {
            self::registerFailedAttempt();
            $errorCode = self::isLockedOut() ? 'locked' : 'invalid';
            return false;
        }

        self::resetLoginGuards();

        session_regenerate_id(true);
        $_SESSION[self::USER_SESSION_KEY] = 1;
        $_SESSION[self::USERNAME_SESSION_KEY] = $username;
        if (class_exists('Csrf')) {
            Csrf::regenerate();
        }

        $errorCode = null;
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                (bool) ($params['secure'] ?? false),
                (bool) ($params['httponly'] ?? true)
            );
        }

        session_destroy();
    }

    private static function configuredUsername(): string
    {
        $username = getenv('VPN_ADMIN_USERNAME');
        if ($username === false || trim($username) === '') {
            return 'login';
        }

        return trim($username);
    }

    private static function verifyPassword(string $providedPassword): bool
    {
        $hash = getenv('VPN_ADMIN_PASSWORD_HASH');
        if (is_string($hash) && $hash !== '') {
            return password_verify($providedPassword, $hash);
        }

        $plain = getenv('VPN_ADMIN_PASSWORD');
        if (!is_string($plain) || $plain === '') {
            $plain = 'pass';
        }

        return hash_equals($plain, $providedPassword);
    }

    private static function resetLoginWindowIfNeeded(): void
    {
        $windowStart = isset($_SESSION[self::LOGIN_WINDOW_KEY]) ? (int) $_SESSION[self::LOGIN_WINDOW_KEY] : 0;
        $now = time();

        if ($windowStart === 0 || ($now - $windowStart) > self::LOGIN_WINDOW_SECONDS) {
            $_SESSION[self::LOGIN_WINDOW_KEY] = $now;
            $_SESSION[self::LOGIN_ATTEMPTS_KEY] = 0;
        }
    }

    private static function registerFailedAttempt(): void
    {
        $attempts = isset($_SESSION[self::LOGIN_ATTEMPTS_KEY]) ? (int) $_SESSION[self::LOGIN_ATTEMPTS_KEY] : 0;
        $attempts++;
        $_SESSION[self::LOGIN_ATTEMPTS_KEY] = $attempts;

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $_SESSION[self::LOGIN_LOCK_UNTIL_KEY] = time() + self::LOCKOUT_SECONDS;
        }
    }

    private static function isLockedOut(): bool
    {
        $lockUntil = isset($_SESSION[self::LOGIN_LOCK_UNTIL_KEY]) ? (int) $_SESSION[self::LOGIN_LOCK_UNTIL_KEY] : 0;

        if ($lockUntil <= 0) {
            return false;
        }

        if (time() >= $lockUntil) {
            self::resetLoginGuards();
            return false;
        }

        return true;
    }

    private static function resetLoginGuards(): void
    {
        unset(
            $_SESSION[self::LOGIN_ATTEMPTS_KEY],
            $_SESSION[self::LOGIN_WINDOW_KEY],
            $_SESSION[self::LOGIN_LOCK_UNTIL_KEY]
        );
    }

    private static function jsonResponse(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($payload);
        exit();
    }
}
