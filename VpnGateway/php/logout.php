<?php
// @changed 2026-04-24 — CSRF validation, POST-only, Auth::logout; fixed stale session key (user_id → userId)
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ./index.php');
    exit();
}

$csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!Csrf::validate($csrfToken)) {
    http_response_code(403);
    echo 'Invalid CSRF token.';
    exit();
}

Auth::logout();

header('Location: ./index.php');
exit();