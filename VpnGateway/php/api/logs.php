<?php
// @since  2026-04-24 — GET endpoint: log tail as JSON (configurable ?limit=)
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

Auth::requireLoginJson();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 80;

try {
    $service = new VpnService();
    $lines = $service->getLogTail($limit);

    echo json_encode([
        'success' => true,
        'data' => [
            'lines' => $lines,
            'text' => implode("\n", $lines),
            'updatedAt' => gmdate('c'),
        ],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to read OpenVPN logs.',
    ]);
}
