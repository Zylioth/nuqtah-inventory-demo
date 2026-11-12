<?php
session_start();

/* Messages are selected from a fixed whitelist — no user input is echoed. */
$noticeTitle = '';
$noticeText  = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'user_exists':
            $noticeTitle = 'Account already exists';
            $noticeText  = 'That username or email address is already registered.';
            break;
        case 'captcha_failed':
            $noticeTitle = 'Verification failed';
            $noticeText  = 'Security verification failed. Please try again.';
            break;
        case 'invalid_role':
            $noticeTitle = 'Choose a role';
            $noticeText  = 'Please select Student or Staff to continue.';
            break;
        default:
            $noticeTitle = 'Something went wrong';
            $noticeText  = 'Please check your details and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00796B">
    <title>Nuqtah Inventory System - Sign Up</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body auth-signup">

<main class="auth">
    <section class="auth-pane auth-pane--form">
        <div class="auth-stack">
            <a class="auth-brand" href="index.php" aria-label="Nuqtah — back to home">
                <img src="assets/img/logoNuqtah_White.png" alt="Nuqtah">
            </a>

            <div class="auth-card">
                <p class="auth-eyebrow">IT Inventory</p>
                <h1 class="auth-title">Create your account</h1>
                <p class="auth-sub">Register with your campus details. An admin approves every new account.</p>

                <?php if ($noticeTitle !== ''): ?>
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

                <form action="actions/signup_process.php" method="POST">
                    <div class="auth-grid">
                        <div class="auth-field">
                            <label class="auth-label" for="signupFullName">Full name</label>
                            <input type="text" id="signupFullName" name="full_name" class="auth-input"
                                   placeholder="Your full name" autocomplete="name" required>
                        </div>
                        <div class="auth-field">
                            <label class="auth-label" for="signupUsername">Username</label>
                            <input type="text" id="signupUsername" name="username" class="auth-input"
                                   placeholder="Username" autocomplete="username" autocapitalize="none"
                                   autocorrect="off" spellcheck="false" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="signupEmail">Email address</label>
                        <input type="email" id="signupEmail" name="email" class="auth-input"
                               placeholder="email@example.com" autocomplete="email" inputmode="email" required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="signupPassword">Password</label>
                        <div class="auth-pass">
                            <input type="password" id="signupPassword" name="password" class="auth-input"
                                   placeholder="Min. 8 characters" autocomplete="new-password"
                                   aria-describedby="pwHint strength-text" required>
                            <button type="button" class="auth-eye" data-toggle-pass="signupPassword"
                                    aria-controls="signupPassword" aria-pressed="false" aria-label="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p class="auth-hint" id="pwHint">Use at least 8 characters with uppercase, lowercase, a number, and a symbol.</p>
                        <div class="progress auth-meter">
                            <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="auth-meter-row">
                            <small id="strength-text">Enter a password</small>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="signupRole">I am a</label>
                        <select id="signupRole" name="role" class="auth-input auth-select" required>
                            <option value="" selected disabled>Select your role</option>
                            <option value="Student">Student</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>

                    <div class="auth-captcha">
                        <div class="auth-captcha-inner">
                            <div class="g-recaptcha" data-sitekey="6LdkWG8sAAAAAORds7qER4QXcMQogo5eWaRuoFfX"></div>
                        </div>
                    </div>

                    <button type="submit" name="signup_btn" class="auth-submit">
                        Create account <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="auth-alt">
                    Already have an account? <a href="login.php">Sign in</a>
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
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="assets/js/validation.js"></script>

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

    /* reCAPTCHA check only runs when the form carries the id that validation.js
       expects; guarded so a missing element no longer throws on load. */
    (function () {
        var form = document.getElementById('registrationForm');
        if (!form) return;
        form.addEventListener('submit', function (event) {
            if (typeof grecaptcha === 'undefined') return;
            if (grecaptcha.getResponse().length === 0) {
                alert('Please verify that you are not a robot before signing up.');
                event.preventDefault();
            }
        });
    })();
</script>
</body>
</html>
