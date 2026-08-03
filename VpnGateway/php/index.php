<?php
// @changed 2026-04-24 — bootstrap/Auth integration; removed raw session_start
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

if (Auth::isLoggedIn()) {
    require_once __DIR__ . '/view/dashboard.php';
} else {
    require_once __DIR__ . '/view/login-form.php';
}