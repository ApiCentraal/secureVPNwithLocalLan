<?php
// @changed 2026-04-24 — component-based view; injects dashboard state as JSON for AJAX bootstrap
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

Auth::requireLoginRedirect('./index.php');

$service = new VpnService();
$dashboardError = null;

try {
    $dashboardState = $service->getDashboardState(90);
} catch (Throwable $exception) {
    $dashboardError = 'Unable to load the initial VPN dashboard state.';
    $dashboardState = [
        'activeState' => 'unknown',
        'ipForwardEnabled' => false,
        'currentConnection' => null,
        'routeMode' => 'stopped',
        'internetReachable' => false,
        'publicIpAddress' => null,
        'connectivityMode' => 'stopped',
        'selectedAction' => 'stop',
        'connections' => [],
        'logLines' => [],
        'updatedAt' => gmdate('c'),
    ];
}

$username = Auth::currentUsername();
$csrfToken = Csrf::token();

$initialStatePayload = [
    'success' => $dashboardError === null,
    'error' => $dashboardError,
    'data' => $dashboardState,
];

$initialStateJson = json_encode(
    $initialStatePayload,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VPN Control Center</title>
    <link href="./view/css/style.css" rel="stylesheet" type="text/css">
</head>
<body class="dashboard-page">
    <div class="screen-backdrop" aria-hidden="true"></div>

    <div class="app-shell">
        <?php require __DIR__ . '/components/topbar.php'; ?>

        <main class="dashboard-layout">
            <?php require __DIR__ . '/components/status-cards.php'; ?>
            <?php require __DIR__ . '/components/connection-panel.php'; ?>
            <?php require __DIR__ . '/components/log-panel.php'; ?>
        </main>
    </div>

    <script id="dashboardInitialState" type="application/json"><?php echo $initialStateJson; ?></script>
    <script>
    window.VPN_APP_CONFIG = {
        csrfToken: <?php echo json_encode($csrfToken); ?>,
        statusEndpoint: './api/status.php',
        actionEndpoint: './api/action.php',
        logsEndpoint: './api/logs.php'
    };
    </script>
    <script src="./view/js/dashboard.js"></script>
</body>
</html>
