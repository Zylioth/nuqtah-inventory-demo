<?php
// Absolute path to ensure it finds the files from anywhere
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendNuqtahEmail($toEmail, $toName, $subject, $message) {
    // Read-only demo: never contact SMTP. Record the intent instead, so the
    // public demo can be hammered without burning the real mailbox or leaking
    // the production Google account. Returns true so callers behave normally.
    if (getenv('DEMO_MODE') === 'true') {
        $logDir = __DIR__ . '/../storage';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @file_put_contents(
            $logDir . '/mail.log',
            sprintf("[%s] to=%s subject=%s\n", date('c'), $toEmail, $subject),
            FILE_APPEND
        );
        return true;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // USE ENV VARIABLES HERE
        $mail->Username   = $_ENV['MAIL_EMAIL']; 
        $mail->Password   = $_ENV['MAIL_PASSWORD']; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Branding the Sender
        $mail->setFrom($_ENV['MAIL_EMAIL'], 'Nuqtah IT System');
        $mail->addAddress($toEmail, $toName);

        // Your new ImgBB link
        $logoUrl = "https://cdn.emailacademy.com/user/80e796f418d60a5e0ccb5c7677f69626d7761b0d13c3413780d65c8ab702b936/Nuqtah_Email_fullres2026_04_21_03_07_02.png";

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
            <div style='background-color: #f4f7f6; padding: 40px 20px; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border-top: 8px solid #00796B;'>
                    
                    <div style='padding: 40px 20px 20px 20px; text-align: center; background-color: #ffffff;'>
                        <img src='$logoUrl' alt='Nuqtah Logo' style='width: 380px; max-width: 100%; height: auto; outline: none; text-decoration: none;'>
                    </div>

                    <div style='padding: 0 40px 40px 40px;'>
                        <div style='color: #333333; line-height: 1.6; font-size: 15px;'>
                            $message
                        </div>
                    </div>

                    <div style='background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #edf2f7;'>
                        <p style='margin: 0; font-size: 14px; font-weight: bold; color: #00796B; letter-spacing: 0.5px;'>Nuqtah IT Inventory System</p>
                        <p style='margin: 8px 0 0; font-size: 12px; color: #94a3b8;'>
                            ICT Department | ITQSHHB <br>
                            This is an automated system notification.
                        </p>
                        <div style='margin-top: 15px; border-top: 1px solid #e0e0e0; padding-top: 15px;'>
                             <p style='font-size: 10px; color: #cbd5e1; margin: 0;'>© 2026 Nuqtah IT. All rights reserved.</p>
                        </div>
                    </div>

                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}