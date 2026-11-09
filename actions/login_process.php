<?php
session_start();
include '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            // --- NEW STATUS CHECK LOGIC ---
            if ($user['account_status'] === 'Pending') {
                header("Location: ../login.php?error=pending_account");
                exit();
            }
            
            if ($user['account_status'] === 'Suspended') {
                header("Location: ../login.php?error=suspended_account");
                exit();
            }
            // ------------------------------

            // Only "Active" users reach this part
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['account_status'];
            $_SESSION['first_login_session'] = true; 

            header("Location: ../inventory_list.php");
            exit();
            
        } else {
            header("Location: ../login.php?error=invalid_credentials");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../login.php?error=database_error");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}