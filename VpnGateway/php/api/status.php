<?php
// @since  2026-04-24 — GET endpoint: full dashboard state as JSON
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

Auth::requireLoginJson();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $service = new VpnService();
    $state = $service->getDashboardState(60);

    echo json_encode([
        'success' => true,
        'data' => $state,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load VPN status.',
    ]);
}
