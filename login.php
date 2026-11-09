<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuqtah Inventory System - Login</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .btn-teal { background-color: #00796B; color: white; }
        .btn-teal:hover { background-color: #00695C; color: white; }
    </style>
</head>
<body>

<div class="container-fluid p-0 vh-100 d-flex flex-column flex-md-row">
    
    <div class="col-md-6 left-panel d-flex flex-column justify-content-center align-items-center p-4">
        <div class="brand-header text-white text-center mb-4">
            <a href="index.php">
                <img src="assets/img/logoNuqtah_White.png" alt="Logo" class="mb-2" style="max-height: 80px;">
            </a>
        </div>

        <div class="card login-card shadow-lg p-4 w-100" style="max-width: 450px;">
            <h3 class="text-center text-muted mb-4">Welcome to NUQTAH</h3>
            
            <?php if (isset($_GET['signup']) && $_GET['signup'] == 'success'): ?>
                <div class="alert alert-success small text-center rounded-4 border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Account created. Please wait for admin approval.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
                <div class="alert alert-success small text-center rounded-4 border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Password updated successfully. Please log in.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger small text-center rounded-4 border-0 shadow-sm" role="alert">
                    <?php 
                        if($_GET['error'] == 'pending_account') {
                            echo "<strong>Access Denied:</strong> Your account is awaiting admin approval.";
                        } elseif($_GET['error'] == 'suspended_account') {
                            echo "<strong>Account Suspended:</strong> Please contact the ICT department for assistance.";
                        } elseif($_GET['error'] == 'database_error') {
                            echo "System error. Please try again later.";
                        } else {
                            echo "Invalid username or password.";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form action="actions/login_process.php" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <input type="text" name="username" class="form-control rounded-pill border-dark px-3" placeholder="Enter your username" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <input type="password" name="password" class="form-control rounded-pill border-dark px-3" placeholder="Enter Your Password" required>
                </div>
                
                <button type="submit" name="login_btn" class="btn btn-teal w-100 rounded-pill text-white fw-bold mb-2 py-2 shadow-sm">LOGIN</button>
                <div class="text-center mb-3">
                    <a href="forgot_password.php" class="text-decoration-none small">Forgot password?</a>
                </div>

                <div class="text-center small">
                    <span class="text-muted">Don't have an account?</span> 
                    <a href="signup.php" class="text-primary text-decoration-none fw-bold">Sign Up</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6 right-panel d-none d-md-block" 
     style="background: linear-gradient(rgb(9 121 105 / 20%), rgb(9 121 105 / 25%)), url('assets/img/Tahfiz_clock.jpg'); background-size: cover; background-position: center;">
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>