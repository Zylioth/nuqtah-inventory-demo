<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuqtah Inventory System - Sign Up</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-page"> 
<div class="container-fluid p-0 min-vh-100 d-flex flex-column flex-md-row">
    
    <div class="col-md-6 left-panel d-flex flex-column justify-content-center align-items-center p-4">
        <div class="brand-header text-white text-center mb-4">
            <a href="index.php">
                <img src="assets/img/logoNuqtah_White.png" alt="Logo" class="mb-2" style="max-height: 80px;">
            </a>   
        </div>

        <div class="card login-card shadow-lg p-4 w-100" style="max-width: 500px;">
            <h3 class="text-center text-muted mb-4">Create Account</h3>
            
            <?php if(isset($_GET['error']) && $_GET['error'] == 'user_exists'): ?>
                <div class="alert alert-warning small text-center">Username or Email already registered.</div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
    <div class="alert alert-danger py-2 small text-center">
        <?php 
            if($_GET['error'] == 'captcha_failed') echo "Security verification failed. Please try again.";
            if($_GET['error'] == 'user_exists') echo "Username or Email is already taken.";
        ?>
    </div>
<?php endif; ?>

            <form action="actions/signup_process.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">Full Name</label>
                        <input type="text" name="full_name" class="form-control rounded-pill border-dark px-3" placeholder="Your Name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small">Username</label>
                        <input type="text" name="username" class="form-control rounded-pill border-dark px-3" placeholder="Username" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small">Email Address</label>
                    <input type="email" name="email" class="form-control rounded-pill border-dark px-3" placeholder="email@example.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small">Password</label>
                    <input type="password" name="password" 
                        class="form-control rounded-pill border-dark px-3" 
                        placeholder="Min. 8 characters" 
                        required>
                    <div class="form-text x-small text-muted ms-2">
                        Must include: Uppercase, Lowercase, Number, and Symbols
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Password Strength</label>
                    <div class="progress" style="height: 5px;">
                        <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small id="strength-text" class="text-muted small ms-1">Enter a password</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">I am a...</label>
                    <select name="role" class="form-select rounded-3" required>
                        <option value="" selected disabled>Select your role</option>
                        <option value="Student">Student</option>
                        <option value="Staff">Staff</option>
                    </select>
                </div>

                <div class="mb-4 d-flex justify-content-center">
                    <div class="g-recaptcha" data-sitekey="6LdkWG8sAAAAAORds7qER4QXcMQogo5eWaRuoFfX"></div>
                 </div> 
                
                <button type="submit" name="signup_btn" class="btn btn-teal w-100 rounded-pill text-white fw-bold mb-3 py-2">SIGN UP</button>
                
                <div class="text-center small">
                    <span class="text-muted">Already have an account?</span> 
                    <a href="login.php" class="text-primary text-decoration-none">Login</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6 right-panel d-none d-md-block" style="background: linear-gradient(rgb(9 121 105 / 20%), rgb(9 121 105 / 25%)), url('assets/img/Tahfiz_clock.jpg'); background-size: cover; background-position: center;">
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="assets/js/validation.js"></script>

<script>
document.getElementById("registrationForm").onsubmit = function(event) {
    var response = grecaptcha.getResponse();
    
    if (response.length == 0) { 
        //browser alert "pop-up"
        alert("Please verify that you are not a robot before signing up.");
        event.preventDefault(); // Stops the form from submitting
        return false;
    }
    return true;
};
</script>

</body>
</html>