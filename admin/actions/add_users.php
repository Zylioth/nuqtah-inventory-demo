<?php
session_start();
include '../../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') exit('Unauthorized');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../manage_users.php?msg=error"); exit();
    }

    // Sanitize & validate inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = trim($_POST['role'] ?? 'Student');
    $password_raw = $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($full_name) < 2 || strlen($password_raw) < 8) {
        header("Location: ../manage_users.php?msg=error"); exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../manage_users.php?msg=error"); exit();
    }
    $allowedRoles = ['Student','Staff','Admin'];
    if (!in_array($role, $allowedRoles, true)) $role = 'Student';

    $password  = password_hash($password_raw, PASSWORD_DEFAULT);
    
    // Default status for new users created by Admin
    $account_status = 'Active';

    try {
        // Check if username or email already exists
        $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->rowCount() > 0) {
            header("Location: ../manage_users.php?msg=exists");
            exit();
        }

        // Insert new user with account_status
        $sql = "INSERT INTO users (full_name, username, email, role, password, account_status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $username, $email, $role, $password, $account_status]);

        header("Location: ../manage_users.php?msg=success");
    } catch (Exception $e) {
        header("Location: ../manage_users.php?msg=error");
    }
}
exit();