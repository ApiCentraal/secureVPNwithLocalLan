<?php
// @changed 2026-04-24 — VpnService::getStatusSnapshot, try/catch; removed shell_exec
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

if (!Auth::isLoggedIn()) {
   http_response_code(401);
   exit();
}

$service = new VpnService();
try {
   $status = $service->getStatusSnapshot();
} catch (Throwable $exception) {
   http_response_code(500);
   exit();
}

$fingerprint = implode('|', [
   (string) ($status['activeState'] ?? 'unknown'),
   !empty($status['ipForwardEnabled']) ? '1' : '0',
   (string) ($status['currentConnection'] ?? 'none'),
]);

if (empty($_SESSION['statusFingerprint'])) {
   $_SESSION['statusFingerprint'] = $fingerprint;
   exit();
}

if ($_SESSION['statusFingerprint'] !== $fingerprint) {
   $_SESSION['statusFingerprint'] = $fingerprint;
   echo 'change';
}
