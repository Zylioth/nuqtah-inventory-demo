<?php
// actions/activate.php
require '../includes/db_connect.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        // 1. Check if the token exists
        $stmt = $pdo->prepare("SELECT user_id, full_name, email FROM users WHERE activation_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Token found! Set status to 'Active' and clear the token
            $update = $pdo->prepare("UPDATE users SET account_status = 'Active', activation_token = NULL WHERE user_id = ?");
            $update->execute([$user['user_id']]);

            // 3. Success - Redirect to login with a success message
            header("Location: ../login.php?msg=activated");
            exit();
        } else {
            // Token not found or already used
            header("Location: ../login.php?error=invalid_token");
            exit();
        }
    } catch (PDOException $e) {
        die("System Error: " . $e->getMessage());
    }
} else {
    // No token provided in the URL
    header("Location: ../login.php");
    exit();
}