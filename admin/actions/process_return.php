<?php
session_start();
include '../../includes/db_connect.php';
include '../../includes/mail_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$request_id = $_POST['request_id'] ?? null;
$return_status = $_POST['return_status'] ?? 'Available'; 
$note = $_POST['return_note'] ?? 'Returned';

if ($request_id) {
    try {
        $pdo->beginTransaction();

        // 2. Modified Query to get User and Asset info for the email
        $stmt = $pdo->prepare("SELECT r.asset_id, r.tag_id, u.full_name, u.email, a.asset_name 
                               FROM borrowing_requests r
                               JOIN users u ON r.user_id = u.user_id
                               JOIN assets a ON r.asset_id = a.asset_id
                               WHERE r.request_id = ?");
        $stmt->execute([$request_id]);
        $details = $stmt->fetch();

        if ($details) {
            $asset_id = $details['asset_id'];
            $tag_id = $details['tag_id'];

            // 3. Update the Tag Status based on the Admin's choice
            if (!empty($tag_id)) {
                $updateTag = $pdo->prepare("UPDATE asset_tags SET status = ? WHERE tag_id = ?");
                $updateTag->execute([$return_status, $tag_id]);
            }

            // 4. Stock Management Logic
            if ($return_status === 'Available') {
                $updateStock = $pdo->prepare("UPDATE assets SET current_stock = current_stock + 1 WHERE asset_id = ?");
                $updateStock->execute([$asset_id]);
            } else {
                // If Damaged/Maintenance, reduce total pool
                $reduceTotal = $pdo->prepare("UPDATE assets SET total_stock = total_stock - 1 WHERE asset_id = ?");
                $reduceTotal->execute([$asset_id]);
            }

            // 5. Finalize the request status
            $updateStatus = $pdo->prepare("UPDATE borrowing_requests SET status = 'Returned', return_note = ?, actual_return_date = NOW() WHERE request_id = ?");
            $updateStatus->execute([$note, $request_id]);

            // --- ADDED: ADMIN LOGGING (Matches 4-column schema) ---
            $admin_name = $_SESSION['full_name'];
            $log_action = "Admin $admin_name processed RETURN for Request #$request_id ({$details['asset_name']}). Condition: $return_status.";
            
            $stmtLog = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_taken) VALUES (?, ?)");
            $stmtLog->execute([$_SESSION['user_id'], $log_action]);
            // --- END LOGGING ---

            $pdo->commit();

            // 6. TRIGGER EMAIL NOTIFICATION
            $subject = "Equipment Return Confirmation - Nuqtah";
            $statusText = ($return_status === 'Available') ? "Good Condition" : "Flagged as $return_status";
            
            $message = "
                <div style='text-align: center; margin-bottom: 25px;'>
                    <span style='background-color: #E3F2FD; color: #1565C0; padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Return Processed</span>
                    <h2 style='color: #333; margin-top: 15px; font-size: 22px;'>Equipment Clearance</h2>
                    <p style='color: #666; font-size: 15px;'>Hello <strong>{$details['full_name']}</strong>, your return has been verified by the ICT Department.</p>
                </div>
                <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; font-size: 13px;'>Item Name</td>
                            <td style='padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 600; text-align: right;'>{$details['asset_name']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; font-size: 13px;'>Condition</td>
                            <td style='padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 600; text-align: right;'>$statusText</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; font-size: 13px;'>Staff Remarks</td>
                            <td style='padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 600; text-align: right;'>" . htmlspecialchars($note) . "</td>
                        </tr>
                    </table>
                </div>
                <div style='border-top: 1px dashed #e2e8f0; padding-top: 20px; text-align: center;'>
                    <p style='margin: 0; font-size: 14px; color: #2E7D32; font-weight: 600;'>✅ Your record for this request is now cleared.</p>
                    <p style='margin: 10px 0 0; font-size: 13px; color: #666;'>Thank you for returning the equipment</p>
                </div>";
                
            sendNuqtahEmail($details['email'], $details['full_name'], $subject, $message);

        } else {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            header("Location: ../view_requests.php?msg=error");
            exit();
        }

        header("Location: ../view_requests.php?msg=returned");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Return Process Error: " . $e->getMessage());
        header("Location: ../view_requests.php?msg=error");
    }
} else {
    header("Location: ../view_requests.php");
}
exit();