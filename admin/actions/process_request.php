<?php
session_start();
include '../../includes/db_connect.php';
include_once '../../includes/mail_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$request_id = $_REQUEST['id'] ?? null;
$action = $_REQUEST['action'] ?? $_REQUEST['status'] ?? null;
$note = $_REQUEST['note'] ?? '';

if ($request_id && $action) {
    try {
        $pdo->beginTransaction();

        $new_status = (strtolower($action) === 'approve' || $action === 'Approved') ? 'Approved' : 'Rejected';

        // 1. Fetch user and asset details
        $stmt = $pdo->prepare("
            SELECT br.status, br.quantity, u.email, u.full_name, a.asset_name 
            FROM borrowing_requests br
            JOIN users u ON br.user_id = u.user_id
            JOIN assets a ON br.asset_id = a.asset_id
            WHERE br.request_id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();

        if ($request && $request['status'] === 'Pending') {
            
            if ($new_status === 'Approved') {
                $qty = (int)$request['quantity'];

                if ($qty > 1) {
                    for ($i = 1; $i < $qty; $i++) {
                        $copyStmt = $pdo->prepare("INSERT INTO borrowing_requests 
                            (user_id, asset_id, quantity, status, request_date, is_read) 
                            SELECT user_id, asset_id, 1, 'Approved', request_date, 0 
                            FROM borrowing_requests WHERE request_id = ?");
                        $copyStmt->execute([$request_id]);
                    }
                    $updateOrig = $pdo->prepare("UPDATE borrowing_requests SET status = 'Approved', quantity = 1, is_read = 0 WHERE request_id = ?");
                    $updateOrig->execute([$request_id]);
                } else {
                    $updateStatus = $pdo->prepare("UPDATE borrowing_requests SET status = 'Approved', is_read = 0 WHERE request_id = ?");
                    $updateStatus->execute([$request_id]);
                }

                $subject = "Request Approved - Nuqtah Inventory";
                $message = "<h3>Hello {$request['full_name']}!</h3><p>Your request for <b>{$request['asset_name']}</b> has been <b>Approved</b>.</p>";
                
            } else {
                $updateStatus = $pdo->prepare("UPDATE borrowing_requests SET status = 'Rejected', admin_note = ?, is_read = 0 WHERE request_id = ?");
                $updateStatus->execute([$note, $request_id]);

                $subject = "Update on Your Borrowing Request";
                $message = "<h3>Hello {$request['full_name']},</h3><p>Your request for <b>{$request['asset_name']}</b> was rejected. Reason: " . htmlspecialchars($note) . "</p>";
            }

            // --- FINAL CORRECTED LOGGING (admin_logs) ---
            $admin_name = $_SESSION['full_name'];
            $action_verb = ($new_status === 'Approved') ? "APPROVED" : "REJECTED";
            
           
            $log_text = "Admin $admin_name $action_verb request #$request_id ({$request['asset_name']}) for {$request['full_name']}.";
            
            // admin_id and action_taken
            $log_sql = "INSERT INTO admin_logs (admin_id, action_taken) VALUES (?, ?)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([$_SESSION['user_id'], $log_text]);

            $pdo->commit();

            sendNuqtahEmail($request['email'], $request['full_name'], $subject, $message);
            header("Location: ../view_requests.php?msg=success");
        } else {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            header("Location: ../view_requests.php?msg=already_processed");
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Approval Error: " . $e->getMessage());
        header("Location: ../view_requests.php?msg=error");
    }
} else {
    header("Location: ../view_requests.php");
}
exit();