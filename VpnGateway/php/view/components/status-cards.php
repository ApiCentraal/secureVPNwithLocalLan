<?php
// @since  2026-04-24 — 4 status cards with data-tone CSS attributes (active/warn/muted)
$activeState = (string) ($dashboardState['activeState'] ?? 'unknown');
$routeMode = (string) ($dashboardState['routeMode'] ?? 'unknown');
$currentConnection = (string) ($dashboardState['currentConnection'] ?? 'none');
$ipForwardEnabled = !empty($dashboardState['ipForwardEnabled']);
$updatedAt = (string) ($dashboardState['updatedAt'] ?? '');

$activeTone = $activeState === 'active' ? 'ok' : 'warn';
$routeTone = $routeMode === 'vpn' ? 'ok' : ($routeMode === 'local' ? 'warn' : 'neutral');
$ipTone = $ipForwardEnabled ? 'ok' : 'neutral';

$routeLabel = 'Stopped';
if ($routeMode === 'vpn') {
    $routeLabel = 'VPN Tunnel';
} elseif ($routeMode === 'local') {
    $routeLabel = 'Local Route';
}
?>
<section class="panel panel-status">
    <div class="panel-head">
        <h2>Connection Status</h2>
        <button type="button" id="refreshStatusButton" class="btn btn-subtle">Refresh now</button>
    </div>

    <div class="status-grid">
        <article class="status-card" data-tone="<?php echo $activeTone; ?>" id="card-active-state">
            <p class="status-label">Service State</p>
            <p class="status-value" id="status-active-state"><?php echo htmlspecialchars(ucfirst($activeState), ENT_QUOTES, 'UTF-8'); ?></p>
        </article>

        <article class="status-card" data-tone="<?php echo $routeTone; ?>" id="card-route-mode">
            <p class="status-label">Routing Mode</p>
            <p class="status-value" id="status-route-mode"><?php echo htmlspecialchars($routeLabel, ENT_QUOTES, 'UTF-8'); ?></p>
        </article>

        <article class="status-card" data-tone="neutral" id="card-current-connection">
            <p class="status-label">Current Profile</p>
            <p class="status-value" id="status-current-connection"><?php echo htmlspecialchars($currentConnection !== '' ? $currentConnection : 'none', ENT_QUOTES, 'UTF-8'); ?></p>
        </article>

        <article class="status-card" data-tone="<?php echo $ipTone; ?>" id="card-ip-forward">
            <p class="status-label">IP Forwarding</p>
            <p class="status-value" id="status-ip-forward"><?php echo $ipForwardEnabled ? 'Enabled' : 'Disabled'; ?></p>
        </article>
    </div>

    <p class="status-updated">Last update: <span id="status-updated-at"><?php echo htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'); ?></span></p>
</section>
