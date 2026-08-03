<?php
// @since  2026-04-24 — POST endpoint: apply VPN selection (CSRF-protected)
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

Auth::requireLoginJson();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.',
    ]);
    exit();
}

$csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!Csrf::validate($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid CSRF token.',
    ]);
    exit();
}

$selection = trim((string) ($_POST['selection'] ?? ''));
if ($selection === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'No VPN profile selection provided.',
    ]);
    exit();
}

try {
    $service = new VpnService();
    $service->applySelection($selection);
    $state = $service->getDashboardState(60);

    echo json_encode([
        'success' => true,
        'message' => 'VPN selection applied successfully.',
        'data' => $state,
    ]);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to apply VPN selection.',
    ]);
}
