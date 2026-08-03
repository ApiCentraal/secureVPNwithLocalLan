<?php
// @changed 2026-04-24 — Auth guard, VpnService::getLogTail; removed raw shell_exec
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

if (!Auth::isLoggedIn()) {
	http_response_code(401);
	exit();
}

$service = new VpnService();

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo implode(PHP_EOL, $service->getLogTail(40));
