<?php
session_start();
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit();
}

$code = trim($_POST['code'] ?? '');
$email = trim($_POST['email'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($newPassword === '' || $confirmPassword === '' || $newPassword !== $confirmPassword || strlen($newPassword) < 8) {
    $error = 'invalid';
    if ($newPassword !== $confirmPassword) {
        $error = 'nomatch';
    }
    header('Location: ../reset_password.php?email=' . urlencode($email) . '&error=' . $error);
    exit();
}

try {
    // Lookup user by email and verify code + expiry
    $stmt = $pdo->prepare('SELECT user_id, password_reset_code, password_reset_expires FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: ../reset_password.php?error=invalid');
        exit();
    }

    if (empty($user['password_reset_code']) || $user['password_reset_code'] !== $code) {
        header('Location: ../reset_password.php?email=' . urlencode($email) . '&error=invalid');
        exit();
    }

    // Check expiry using PHP time in UTC to match storage
    if (empty($user['password_reset_expires'])) {
        header('Location: ../reset_password.php?email=' . urlencode($email) . '&error=invalid');
        exit();
    }
    $expires = new DateTime($user['password_reset_expires'], new DateTimeZone('UTC'));
    $now = new DateTime('now', new DateTimeZone('UTC'));
    if ($expires <= $now) {
        header('Location: ../reset_password.php?email=' . urlencode($email) . '&error=invalid');
        exit();
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $pdo->prepare('UPDATE users SET password = ?, password_reset_code = NULL, password_reset_expires = NULL WHERE user_id = ?');
    $update->execute([$newHash, $user['user_id']]);

    header('Location: ../login.php?reset=success');
    exit();
} catch (Exception $e) {
    error_log('Reset password error: ' . $e->getMessage());
    header('Location: ../reset_password.php?email=' . urlencode($email) . '&error=invalid');
    exit();
}
