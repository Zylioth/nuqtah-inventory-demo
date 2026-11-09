<?php
session_start();
include '../../includes/db_connect.php';

// 1. Security: Only Admins can access this script
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    exit('Unauthorized Access');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $target_id = $_POST['id'];
    $current_admin = $_SESSION['user_id'];

    // CSRF validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../manage_users.php?msg=error"); exit();
    }

    // 2. Prevent Self-Deletion (Safety Lock)
    if ($target_id == $current_admin) {
        header("Location: ../manage_users.php?msg=self_delete_error");
        exit();
    }

    try {
        // 3. Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$target_id]);

        // Redirect with success message
        header("Location: ../manage_users.php?msg=deleted");
        exit();

    } catch (PDOException $e) {
        // If the user is linked to other records (like active requests), 
        // this might fail depending on your Database Foreign Keys.
        header("Location: ../manage_users.php?msg=error");
        exit();
    }
} else {
    // If no ID is provided or wrong method, just go back
    header("Location: ../manage_users.php");
    exit();
}