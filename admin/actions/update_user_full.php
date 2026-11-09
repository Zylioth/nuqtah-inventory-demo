<?php
session_start();
include '../../includes/db_connect.php';
include_once '../../includes/mail_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') exit('Unauthorized');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../manage_users.php?msg=error"); exit();
    }

    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_role = trim($_POST['role'] ?? 'Student');
    $new_status = trim($_POST['account_status'] ?? 'Active'); 

    // basic validation
    if (strlen($username) < 3 || strlen($full_name) < 2) { header("Location: ../manage_users.php?msg=error"); exit(); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { header("Location: ../manage_users.php?msg=error"); exit(); }

    // Check for duplicates (username or email) excluding this user
    $checkDup = $pdo->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $checkDup->execute([$username, $email, $user_id]);
    if ($checkDup->rowCount() > 0) {
        header("Location: ../manage_users.php?msg=exists"); exit();
    }

    // Safety checks for the current Admin (prevent self-lockout)
    if ($user_id == $_SESSION['user_id']) {
        if ($new_role !== 'Admin' || $new_status !== 'Active') {
            header("Location: ../manage_users.php?msg=error");
            exit();
        }
    }

    try {
        // 1. Get current status BEFORE updating
        $checkStmt = $pdo->prepare("SELECT account_status FROM users WHERE user_id = ?");
        $checkStmt->execute([$user_id]);
        $old_status = $checkStmt->fetchColumn();

        // 2. Perform the Update
        $sql = "UPDATE users SET 
                full_name = ?, 
                username = ?, 
                email = ?, 
                role = ?, 
                account_status = ?,
                activation_token = CASE WHEN ? = 'Active' THEN NULL ELSE activation_token END 
                WHERE user_id = ?";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $username, $email, $new_role, $new_status, $new_status, $user_id]);

        // 3. Trigger Email ONLY if status changed from Pending to Active
        if ($old_status === 'Pending' && $new_status === 'Active') {
            $subject = "Account Activated - Nuqtah Inventory System";
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/Nuqtah_IT', '/');
            $message = "
                    <div style='text-align: center; margin-bottom: 25px;'>
                        <span style='background-color: #E0F2F1; color: #00796B; padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Account Verified</span>
                        <h2 style='color: #333; margin-top: 15px; font-size: 24px;'>Welcome to Nuqtah!</h2>
                        <p style='color: #666; font-size: 15px;'>Hello <strong>" . htmlspecialchars($full_name) . "</strong>, your registration has been approved by the ICT Department.</p>
                    </div>

                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 30px;'>
                        <p style='margin: 0 0 15px 0; font-size: 14px; color: #475569; font-weight: 600;'>With your new account, you can:</p>
                        <ul style='margin: 0; padding: 0 0 0 20px; color: #1e293b; font-size: 14px; line-height: 2;'>
                            <li>Browse available IT assets and equipment</li>
                            <li>Submit borrowing requests online</li>
                            <li>Track your borrowing history and due dates</li>
                        </ul>
                    </div>

                    <div style='text-align: center; margin-bottom: 30px;'>
                        <a href='{$appUrl}/login.php' style='display: inline-block; background-color: #00796B; color: #ffffff; padding: 14px 32px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 4px 10px rgba(0, 121, 107, 0.25);'>Login to Your Account</a>
                    </div>

                    <div style='border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: center;'>
                        <p style='color: #94a3b8; font-size: 11px; margin: 0;'>
                            If the button above doesn't work, copy and paste this link into your browser: <br>
                            <span style='color: #00796B;'>{$appUrl}/login.php</span>
                        </p>
                    </div>";

                    
            // Send email and log if it fails
            if (!sendNuqtahEmail($email, $full_name, $subject, $message)) {
                error_log("Failed to send activation email to: $email");
            }

            // Redirect with 'activated' message
            header("Location: ../manage_users.php?msg=activated");
        } else {
            // General update redirect
            header("Location: ../manage_users.php?msg=updated");
        }
        
    } catch (Exception $e) {
        error_log("Update User Error: " . $e->getMessage());
        header("Location: ../manage_users.php?msg=error");
    }
} else {
    header("Location: ../manage_users.php");
}
exit();