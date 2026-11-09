<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../change_password.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Basic validations
if ($new !== $confirm) {
    header('Location: ../change_password.php?error=nomatch');
    exit();
}

if (strlen($new) < 8) {
    header('Location: ../change_password.php?error=weak');
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password'])) {
        header('Location: ../change_password.php?error=wrong_current');
        exit();
    }

    $new_hash = password_hash($new, PASSWORD_DEFAULT);
    $update = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    $update->execute([$new_hash, $user_id]);

    header('Location: ../change_password.php?success=1');
    exit();

} catch (PDOException $e) {
    header('Location: ../change_password.php?error=database');
    exit();
}
