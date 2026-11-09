<?php
session_start();
include '../includes/db_connect.php';
include '../includes/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../forgot_password.php?sent=1');
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT user_id, full_name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate only the 6-digit code and expiry (use UTC to avoid timezone issues).
        $code = random_int(100000, 999999);
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $dt->add(new DateInterval('PT1H')); // 1 hour
        $expires = $dt->format('Y-m-d H:i:s');

        // Save the code and expiry (token left NULL)
        $update = $pdo->prepare('UPDATE users SET password_reset_code = ?, password_reset_expires = ? WHERE user_id = ?');
        $update->execute([$code, $expires, $user['user_id']]);

        $subject = 'Reset your Nuqtah password';
        $message = "<p>Hello " . htmlspecialchars($user['full_name']) . ",</p>"
                 . "<p>We received a request to reset your password. Please use the 6-digit code below on the password reset page to change your password. The code expires in 1 hour.</p>"
                 . "<div style='text-align:center; margin: 30px 0;'>"
                 . "<span style='display:inline-block; background-color:#f1f7f6; color:#00796B; padding:12px 26px; border-radius:12px; font-weight:700; font-size:20px; letter-spacing:4px;'>" . htmlspecialchars($code) . "</span>"
                 . "</div>"
                 . "<p>If you didn’t request this, you can safely ignore this email.</p>";

        sendNuqtahEmail($email, $user['full_name'], $subject, $message);
    }
} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
}

// Redirect user to the reset page with their email prefilled so they can enter the code.
header('Location: ../reset_password.php?email=' . urlencode($email) . '&sent=1');
exit();
