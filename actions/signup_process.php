<?php
// Go up one level to find the includes folder
require '../includes/db_connect.php';
// Include the mail helper for the verification email
include_once '../includes/mail_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_btn'])) {
    
    // 1. Verify reCAPTCHA
    $recaptcha_secret = "6LdkWG8sAAAAALhGukLq7d3il7siuoZTtnAFEPdN";
    $recaptcha_response = $_POST['g-recaptcha-response'];

    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response";
    $response = file_get_contents($verify_url);
    $response_keys = json_decode($response, true);

    if (!$response_keys["success"]) {
        header("Location: ../signup.php?error=captcha_failed");
        exit();
    }

    // Capture Input
    $full_name = $_POST['full_name'];
    $username  = $_POST['username'];
    $email     = $_POST['email'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // --- UPDATED ROLE LOGIC START ---
    $role = $_POST['role']; 
    
    // Security: Validate that the role is strictly Student or Staff
    $allowed_roles = ['Student', 'Staff'];
    if (!in_array($role, $allowed_roles)) {
        header("Location: ../signup.php?error=invalid_role");
        exit();
    }
    // --- UPDATED ROLE LOGIC END ---

    $status    = 'Pending';

    // 2. Generate a secure random activation token
    $token = bin2hex(random_bytes(32));

    // 3. Updated SQL to include activation_token column
    $sql = "INSERT INTO users (full_name, username, email, password, role, account_status, activation_token) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$full_name, $username, $email, $password, $role, $status, $token]);
        
        // 1. Get the base URL from your env (loaded via db_connect.php)
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost/Nuqtah_IT';

        // 2. Build the link using that base
        $activateLink = $baseUrl . "/actions/activate.php?token=$token";

        $subject = "Verify Your Nuqtah Account";
        $message = "
            <h3>Welcome to Nuqtah, $full_name!</h3>
            <p>Thank you for registering as a <b>$role</b>. Before the ICT department can approve your access, please verify your email address by clicking the button below:</p>
            <br>
            <a href='$activateLink' style='display:inline-block; background: #00796B; color: white; padding: 12px 25px; text-decoration: none; border-radius: 30px; font-weight: bold;'>Verify Email Address</a>
            <br>
            <p style='color: #888; font-size: 11px; margin-top: 20px;'>If the button above does not work, copy and paste this link into your browser:<br>$activateLink</p>";

        sendNuqtahEmail($email, $full_name, $subject, $message);

        header("Location: ../login.php?signup=success");
        exit();

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header("Location: ../signup.php?error=user_exists");
        } else {
            die("Database Error: " . $e->getMessage());
        }
        exit();
    }
}