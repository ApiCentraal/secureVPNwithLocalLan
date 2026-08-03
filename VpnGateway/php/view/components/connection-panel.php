<?php
// @since  2026-04-24 — VPN profile selector with CSRF-protected apply/stop/LAN-only actions
$activeState = (string) ($dashboardState['activeState'] ?? 'unknown');
$selectedAction = (string) ($dashboardState['selectedAction'] ?? 'stop');
$connections = is_array($dashboardState['connections'] ?? null) ? $dashboardState['connections'] : [];
$internetReachable = !empty($dashboardState['internetReachable']);
$publicIpAddress = trim((string) ($dashboardState['publicIpAddress'] ?? ''));
$wanSummary = $internetReachable ? 'WAN uplink online' : 'WAN uplink unavailable';
if ($internetReachable && $publicIpAddress !== '' && $publicIpAddress !== 'null') {
    $wanSummary .= ' · ' . $publicIpAddress;
}
$showWanWarning = $activeState === 'active' && !$internetReachable;
?>
<section class="panel panel-control">
    <div class="panel-head">
        <h2>Route Switcher</h2>
        <p>Choose a VPN profile or switch to LAN-only mode when the WAN uplink is unavailable.</p>
    </div>

    <div id="wanWarningBanner" class="app-alert error<?php echo $showWanWarning ? '' : ' is-hidden'; ?>" role="alert" aria-live="assertive">
        VPN is active, but the WAN uplink is down. Internet-bound traffic may not reach the provider. Use LAN-only mode if you only need local access.
    </div>

    <div class="app-alert info">
        <?php if ($internetReachable): ?>
            <?php echo htmlspecialchars($wanSummary, ENT_QUOTES, 'UTF-8'); ?>. Keep VPN mode for anonymous egress or use LAN-only mode if you want to isolate the local network.
        <?php else: ?>
            <?php echo htmlspecialchars($wanSummary, ENT_QUOTES, 'UTF-8'); ?>. Use LAN-only mode to keep local access until internet returns.
        <?php endif; ?>
    </div>

    <div id="appAlert" class="app-alert is-hidden" role="status" aria-live="polite"></div>

    <form id="vpnSwitchForm" class="control-form" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="connection-list" id="connectionList">
            <?php foreach ($connections as $connection): ?>
                <?php
                $connectionId = (string) ($connection['id'] ?? '');
                $connectionLabel = (string) ($connection['label'] ?? $connectionId);
                $isActive = !empty($connection['active']);
                $isChecked = $selectedAction === $connectionId;
                ?>
                <label class="connection-item <?php echo $isActive ? 'is-active' : ''; ?>">
                    <input type="radio" name="selection" value="<?php echo htmlspecialchars($connectionId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isChecked ? 'checked="checked"' : ''; ?>>
                    <span class="connection-copy">
                        <span class="connection-name"><?php echo htmlspecialchars($connectionLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="connection-meta"><?php echo $isActive ? 'Currently active' : 'VPN profile'; ?></span>
                    </span>
                </label>
            <?php endforeach; ?>

            <label class="connection-item is-system">
                <input type="radio" name="selection" value="stop" <?php echo $selectedAction === 'stop' ? 'checked="checked"' : ''; ?>>
                <span class="connection-copy">
                    <span class="connection-name">Stop VPN routing</span>
                    <span class="connection-meta">Disable tunnel and forwarding</span>
                </span>
            </label>

            <label class="connection-item is-system">
                <input type="radio" name="selection" value="stop-route-local" <?php echo $selectedAction === 'stop-route-local' ? 'checked="checked"' : ''; ?>>
                <span class="connection-copy">
                    <span class="connection-name">LAN only mode</span>
                    <span class="connection-meta">Stop tunnel but keep local forwarding when WAN is down</span>
                </span>
            </label>
        </div>

        <div class="control-actions">
            <button type="submit" id="applySelectionButton" class="btn btn-primary">Apply selection</button>
            <button type="button" class="btn btn-subtle" data-selection="stop">Stop VPN</button>
            <button type="button" class="btn btn-subtle" data-selection="stop-route-local">LAN only mode</button>
        </div>
    </form>
</section>
