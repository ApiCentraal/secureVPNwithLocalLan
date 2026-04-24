<?php
// @changed 2026-04-24 — CSRF token injected; lockout and error messaging
declare(strict_types=1);

$errorMessage = '';
if (isset($_SESSION['errorMessage'])) {
    $errorMessage = (string) $_SESSION['errorMessage'];
    unset($_SESSION['errorMessage']);
}

$csrfToken = Csrf::token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VPN Gateway Login</title>
    <link href="./view/css/style.css" rel="stylesheet" type="text/css">
</head>
<body class="auth-page">
    <div class="screen-backdrop" aria-hidden="true"></div>

    <main class="auth-shell">
        <section class="auth-card">
            <p class="auth-eyebrow">Secure VPN Gateway</p>
            <h1 class="auth-title">Sign in to continue</h1>
            <p class="auth-subtitle">Manage outbound tunnel profiles and local route fallback from one console.</p>

            <?php if ($errorMessage !== ''): ?>
                <div class="app-alert error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form action="login-action.php" method="post" id="frmLogin" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <label class="field-column" for="user_name">
                    <span class="field-label">Username</span>
                    <input name="user_name" id="user_name" type="text" class="demo-input-box" autocomplete="username" required>
                    <span id="user_info" class="error-info"></span>
                </label>

                <label class="field-column" for="password">
                    <span class="field-label">Password</span>
                    <input name="password" id="password" type="password" class="demo-input-box" autocomplete="current-password" required>
                    <span id="password_info" class="error-info"></span>
                </label>

                <button type="submit" name="login" value="1" class="btn btn-primary btnLogin">Login</button>
            </form>
        </section>
    </main>

    <script>
    (function () {
        var form = document.getElementById('frmLogin');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            var valid = true;
            var userInfo = document.getElementById('user_info');
            var passwordInfo = document.getElementById('password_info');
            var userName = document.getElementById('user_name').value.trim();
            var password = document.getElementById('password').value;

            userInfo.textContent = '';
            passwordInfo.textContent = '';

            if (userName === '') {
                userInfo.textContent = 'required';
                valid = false;
            }

            if (password === '') {
                passwordInfo.textContent = 'required';
                valid = false;
            }

            if (!valid) {
                event.preventDefault();
            }
        });
    })();
    </script>
</body>
</html>