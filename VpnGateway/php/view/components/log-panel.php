<?php
// @since  2026-04-24 — log output panel component; auto-refreshed via dashboard.js
$logLines = is_array($dashboardState['logLines'] ?? null) ? $dashboardState['logLines'] : [];
$logText = trim(implode(PHP_EOL, $logLines));
if ($logText === '') {
    $logText = 'No log lines available.';
}
?>
<section class="panel panel-log">
    <div class="panel-head">
        <h2>OpenVPN Log Stream</h2>
        <button type="button" id="refreshLogButton" class="btn btn-subtle">Refresh log</button>
    </div>

    <pre id="vpnLogOutput" class="log-output"><?php echo htmlspecialchars($logText, ENT_QUOTES, 'UTF-8'); ?></pre>
</section>
