<?php
// @since  2026-04-24 — VPN profile selector with CSRF-protected apply/stop/route-local actions
$selectedAction = (string) ($dashboardState['selectedAction'] ?? 'stop');
$connections = is_array($dashboardState['connections'] ?? null) ? $dashboardState['connections'] : [];
?>
<section class="panel panel-control">
    <div class="panel-head">
        <h2>Route Switcher</h2>
        <p>Choose a VPN profile or switch to local routing.</p>
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
                    <span class="connection-name">Route locally</span>
                    <span class="connection-meta">Stop tunnel but keep local forwarding</span>
                </span>
            </label>
        </div>

        <div class="control-actions">
            <button type="submit" id="applySelectionButton" class="btn btn-primary">Apply selection</button>
            <button type="button" class="btn btn-subtle" data-selection="stop">Stop VPN</button>
            <button type="button" class="btn btn-subtle" data-selection="stop-route-local">Route local</button>
        </div>
    </form>
</section>
