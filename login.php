<?php
session_start();

/* Messages are selected from a fixed whitelist — no user input is echoed. */
$noticeType  = '';
$noticeTitle = '';
$noticeText  = '';

if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    $noticeType  = 'success';
    $noticeTitle = 'Account created';
    $noticeText  = 'Please wait for admin approval before signing in.';
} elseif (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $noticeType  = 'success';
    $noticeTitle = 'Password updated';
    $noticeText  = 'Your new password is ready. Please log in.';
} elseif (isset($_GET['error'])) {
    $noticeType = 'error';
    switch ($_GET['error']) {
        case 'pending_account':
            $noticeTitle = 'Access denied';
            $noticeText  = 'Your account is awaiting admin approval.';
            break;
        case 'suspended_account':
            $noticeTitle = 'Account suspended';
            $noticeText  = 'Please contact the ICT department for assistance.';
            break;
        case 'database_error':
            $noticeTitle = 'System error';
            $noticeText  = 'Please try again later.';
            break;
        default:
            $noticeTitle = 'Sign in failed';
            $noticeText  = 'Invalid username or password.';
    }
}

/* Highlight the fields only when the credentials themselves were rejected. */
$markInvalid = ($noticeType === 'error' && !in_array($_GET['error'] ?? '', ['pending_account', 'suspended_account', 'database_error'], true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00796B">
    <title>Nuqtah Inventory System - Login</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">

<main class="auth">
    <section class="auth-pane auth-pane--form">
        <div class="auth-stack">
            <a class="auth-brand" href="index.php" aria-label="Nuqtah — back to home">
                <img src="assets/img/logoNuqtah_White.png" alt="Nuqtah">
            </a>

            <div class="auth-card">
                <p class="auth-eyebrow">IT Inventory</p>
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-sub">Sign in to browse campus equipment and track your requests.</p>

                <?php if ($noticeType === 'success'): ?>
                    <div class="auth-notice auth-notice--success" role="status">
                        <svg class="auth-check" viewBox="0 0 40 40" aria-hidden="true" focusable="false">
                            <circle cx="20" cy="20" r="18"/>
                            <path d="M12.5 20.5l5 5 10-11"/>
                        </svg>
                        <div class="auth-notice-text">
                            <strong><?= htmlspecialchars($noticeTitle) ?></strong>
                            <span><?= htmlspecialchars($noticeText) ?></span>
                        </div>
                    </div>
                <?php elseif ($noticeType === 'error'): ?>
                    <div class="auth-notice auth-notice--error" role="alert">
                        <svg class="auth-alert" viewBox="0 0 40 40" aria-hidden="true" focusable="false">
                            <circle cx="20" cy="20" r="18"/>
                            <path d="M20 11.5v10.5"/>
                            <circle class="auth-alert-dot" cx="20" cy="28" r="1.6"/>
                        </svg>
                        <div class="auth-notice-text">
                            <strong><?= htmlspecialchars($noticeTitle) ?></strong>
                            <span><?= htmlspecialchars($noticeText) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="actions/login_process.php" method="POST">
                    <div class="auth-field">
                        <label class="auth-label" for="loginUsername">Username</label>
                        <input type="text" id="loginUsername" name="username"
                               class="auth-input<?= $markInvalid ? ' is-invalid-field' : '' ?>"
                               placeholder="Enter your username"
                               autocomplete="username" autocapitalize="none" autocorrect="off"
                               spellcheck="false" required<?= $markInvalid ? ' aria-invalid="true"' : '' ?>>
                    </div>

                    <div class="auth-field">
                        <div class="auth-label">
                            <label for="loginPassword">Password</label>
                            <a href="forgot_password.php">Forgot password?</a>
                        </div>
                        <div class="auth-pass">
                            <input type="password" id="loginPassword" name="password"
                                   class="auth-input<?= $markInvalid ? ' is-invalid-field' : '' ?>"
                                   placeholder="Enter your password"
                                   autocomplete="current-password" required<?= $markInvalid ? ' aria-invalid="true"' : '' ?>>
                            <button type="button" class="auth-eye" data-toggle-pass="loginPassword"
                                    aria-controls="loginPassword" aria-pressed="false" aria-label="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login_btn" class="auth-submit">
                        Sign in <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="auth-alt">
                    New to Nuqtah? <a href="signup.php">Create an account</a>
                </p>
            </div>

            <p class="auth-legal">ITQSHHB ICT Department · Nuqtah Inventory</p>
        </div>
    </section>

    <aside class="auth-pane auth-pane--visual" aria-hidden="true">
        <div class="auth-visual-inner">
            <span class="auth-visual-kicker">ITQSHHB</span>
            <p class="auth-visual-title">Your campus IT, in one place.</p>
            <p class="auth-visual-note">Request equipment, follow live stock, and return it on time — all from one dashboard.</p>
        </div>
    </aside>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-pass'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', (show ? 'Hide' : 'Show') + ' password');
            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !show);
                icon.classList.toggle('bi-eye-slash', show);
            }
        });
    });
</script>
</body>
</html>
