<?php
// @since  2026-04-24 — app header component with username chip and CSRF-protected logout form
$displayUsername = htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8');
?>
<header class="app-header">
    <div class="brand-block">
        <p class="brand-eyebrow">Secure Gateway</p>
        <h1 class="brand-title">VPN Control Center</h1>
        <p class="brand-subtitle">Switch routes safely and monitor tunnel health in real time.</p>
    </div>
    <div class="header-actions">
        <span class="identity-chip">
            <span class="identity-dot" aria-hidden="true"></span>
            Signed in as <?php echo $displayUsername; ?>
        </span>
        <form method="post" action="./logout.php" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn btn-ghost">Logout</button>
        </form>
    </div>
</header>
