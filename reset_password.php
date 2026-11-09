<?php
session_start();
include 'includes/db_connect.php';

// Accept redirect from forgot flow which provides an email and sent flag
$prefillEmail = trim($_GET['email'] ?? '');
$sentFlag = trim($_GET['sent'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .btn-teal { background-color: #00796B; color: white; }
        .btn-teal:hover { background-color: #00695C; color: white; }
        .auth-card { max-width: 450px; margin: 40px auto; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid p-0 vh-100 d-flex flex-column flex-md-row">
    <div class="col-md-6 left-panel d-flex flex-column justify-content-center align-items-center p-4">
        <div class="brand-header text-white text-center mb-4">
            <a href="index.php">
                <img src="assets/img/logoNuqtah_White.png" alt="Logo" class="mb-2" style="max-height: 80px;">
            </a>
        </div>

        <div class="card login-card shadow-lg p-4 w-100 auth-card">
            <h3 class="text-center text-muted mb-4">Create a New Password</h3>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid'): ?>
                <div class="alert alert-danger small text-center rounded-4 border-0 shadow-sm" role="alert">
                    Invalid or expired reset link. Please request a new link.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'nomatch'): ?>
                <div class="alert alert-danger small text-center rounded-4 border-0 shadow-sm" role="alert">
                    Passwords do not match. Please try again.
                </div>
            <?php endif; ?>

            <?php if ($sentFlag == '1' && $prefillEmail !== ''): ?>
                <div class="alert alert-success small text-center rounded-4 border-0 shadow-sm" role="alert">
                    A 6-digit code was sent to <strong><?php echo htmlspecialchars($prefillEmail); ?></strong>. Enter it below to reset your password.
                </div>
            <?php endif; ?>

            <!-- Unified reset form: email + code + new password -->
            <form action="actions/reset_password.php" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                    <input type="email" name="email" class="form-control rounded-pill border-dark px-3" placeholder="Enter your email" value="<?php echo htmlspecialchars($prefillEmail); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">RESET CODE</label>
                    <input type="text" name="code" class="form-control rounded-pill border-dark px-3" placeholder="Enter 6-digit code" minlength="6" maxlength="6" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">NEW PASSWORD</label>
                    <input type="password" name="new_password" class="form-control rounded-pill border-dark px-3" placeholder="Enter new password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">CONFIRM PASSWORD</label>
                    <input type="password" name="confirm_password" class="form-control rounded-pill border-dark px-3" placeholder="Repeat new password" required>
                </div>
                <button type="submit" class="btn btn-teal w-100 rounded-pill text-white fw-bold mb-3 py-2 shadow-sm">Reset Password</button>
                <div class="text-center small">
                    <a href="forgot_password.php" class="text-primary text-decoration-none fw-bold">Request a new code</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6 right-panel d-none d-md-block" style="background: linear-gradient(rgb(9 121 105 / 20%), rgb(9 121 105 / 25%)), url('assets/img/Tahfiz_clock.jpg'); background-size: cover; background-position: center;">
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
