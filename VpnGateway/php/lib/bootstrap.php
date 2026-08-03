<?php
// @since  2026-04-24 — secure session bootstrap; autoloads Auth, Csrf, VpnService
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Strict', '', $isHttps, true);
    }

    session_name('vpn_gateway_session');
    session_start();
}

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/VpnService.php';
