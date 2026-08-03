<?php
// @since  2026-04-24 — 5 status cards with WAN uplink awareness and data-tone CSS attributes
$activeState = (string) ($dashboardState['activeState'] ?? 'unknown');
$routeMode = (string) ($dashboardState['routeMode'] ?? 'unknown');
$internetReachable = !empty($dashboardState['internetReachable']);
$publicIpAddress = trim((string) ($dashboardState['publicIpAddress'] ?? ''));
$currentConnection = (string) ($dashboardState['currentConnection'] ?? 'none');
$ipForwardEnabled = !empty($dashboardState['ipForwardEnabled']);
$updatedAt = (string) ($dashboardState['updatedAt'] ?? '');

$activeTone = $activeState === 'active' ? 'ok' : 'warn';
$routeTone = $routeMode === 'vpn' ? ($internetReachable ? 'ok' : 'warn') : ($routeMode === 'local' ? 'warn' : 'neutral');
$wanTone = $internetReachable ? 'ok' : 'warn';
$ipTone = $ipForwardEnabled ? 'ok' : 'neutral';

$routeLabel = 'Stopped';
if ($routeMode === 'vpn') {
    $routeLabel = $internetReachable ? 'VPN Tunnel' : 'VPN Tunnel (WAN down)';
} elseif ($routeMode === 'local') {
    $routeLabel = 'LAN Only';
}

$wanLabel = $internetReachable ? 'Online' : 'Offline';
if ($internetReachable && $publicIpAddress !== '' && $publicIpAddress !== 'null') {
    $wanLabel .= ' · ' . $publicIpAddress;
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

        <article class="status-card" data-tone="<?php echo $wanTone; ?>" id="card-wan-uplink">
            <p class="status-label">WAN Uplink</p>
            <p class="status-value" id="status-wan-uplink"><?php echo htmlspecialchars($wanLabel, ENT_QUOTES, 'UTF-8'); ?></p>
        </article>

        <article class="status-card" data-tone="<?php echo $ipTone; ?>" id="card-ip-forward">
            <p class="status-label">IP Forwarding</p>
            <p class="status-value" id="status-ip-forward"><?php echo $ipForwardEnabled ? 'Enabled' : 'Disabled'; ?></p>
        </article>
    </div>

    <p class="status-updated">Last update: <span id="status-updated-at"><?php echo htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'); ?></span></p>
</section>
