<?php
// @changed 2026-04-24 — CSRF validation, brute-force lockout, POST-method guard
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ./index.php');
    exit();
}

$csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!Csrf::validate($csrfToken)) {
    $_SESSION['errorMessage'] = 'Session expired. Please retry login.';
    header('Location: ./index.php');
    exit();
}

$username = trim((string) ($_POST['user_name'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$errorCode = null;
if (!Auth::attemptLogin($username, $password, $errorCode)) {
    if ($errorCode === 'locked') {
        $_SESSION['errorMessage'] = 'Too many attempts. Please wait 5 minutes.';
    } elseif ($errorCode === 'missing') {
        $_SESSION['errorMessage'] = 'Username and password are required.';
    } else {
        $_SESSION['errorMessage'] = 'Invalid credentials.';
    }
}

header('Location: ./index.php');
exit();
