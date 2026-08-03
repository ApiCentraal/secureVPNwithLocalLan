<?php
// @since  2026-04-24 — GET endpoint: service health checks (public, no auth required)
declare(strict_types=1);

/**
 * GET /api/health.php
 *
 * Public endpoint — no authentication required — so it is safe to use with
 * external uptime/monitoring tools.  Returns HTTP 200 when all checks pass,
 * HTTP 503 when one or more checks fail.
 *
 * Response body (JSON):
 * {
 *   "ok": true|false,
 *   "checks": {
 *     "binary_exists":  { "ok": true, "detail": "/usr/local/bin/vpnadmin.sh" },
 *     "binary_executable": { "ok": true },
 *     "log_readable":   { "ok": true, "detail": "/var/log/openvpn/ovpn.log" },
 *     "php_session":    { "ok": true }
 *   },
 *   "ts": "2024-01-01T00:00:00+00:00"
 * }
 */

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── Checks ────────────────────────────────────────────────────────────────────

$checks = [];

// 1. Binary exists
$binaryPath = '/usr/local/bin/vpnadmin.sh';
$binaryExists = file_exists($binaryPath);
$checks['binary_exists'] = [
    'ok'     => $binaryExists,
    'detail' => $binaryPath,
];

// 2. Binary is executable by current process
$binaryExecutable = $binaryExists && is_executable($binaryPath);
$checks['binary_executable'] = [
    'ok' => $binaryExecutable,
];

// 3. Log file readable (file may not exist yet on a fresh install — treat as non-fatal)
$logPath = '/var/log/openvpn/ovpn.log';
$logReadable = file_exists($logPath) && is_readable($logPath);
$checks['log_readable'] = [
    'ok'     => $logReadable,
    'detail' => $logPath,
];

// 4. PHP session subsystem available (sanity check)
$sessionOk = function_exists('session_start') && session_status() !== PHP_SESSION_DISABLED;
$checks['php_session'] = [
    'ok' => $sessionOk,
];

// ── Aggregate result ──────────────────────────────────────────────────────────

// binary_exists + binary_executable are hard failures.
// log_readable is a soft failure (warn but still 200) on first boot.
$hardFail = !$checks['binary_exists']['ok'] || !$checks['binary_executable']['ok'] || !$checks['php_session']['ok'];

$allOk = !$hardFail;

http_response_code($allOk ? 200 : 503);

echo json_encode(
    [
        'ok'     => $allOk,
        'checks' => $checks,
        'ts'     => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
    ],
    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
