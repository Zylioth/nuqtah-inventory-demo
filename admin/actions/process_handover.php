<?php
session_start();
include '../../includes/db_connect.php';
include '../../includes/mail_helper.php'; // Include the helper

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $tag_id = $_POST['assigned_tag'] ?? null; 
    $handover_note = $_POST['handover_note'] ?? '';

    if ($request_id && $tag_id) {
        try {
            $pdo->beginTransaction();

            // 1. Get Asset, User, and Request details
            $stmtDetails = $pdo->prepare("SELECT a.asset_id, a.category, a.asset_name, 
                                                u.full_name, u.email, r.return_date
                                         FROM borrowing_requests r 
                                         JOIN assets a ON r.asset_id = a.asset_id 
                                         JOIN users u ON r.user_id = u.user_id
                                         WHERE r.request_id = ?");
            $stmtDetails->execute([$request_id]);
            $details = $stmtDetails->fetch();
            
            if (!$details) throw new Exception("Request not found");

            $asset_id = $details['asset_id'];
            $category = strtolower($details['category']); 

            // 2. Logic Branching for Stock
            if ($category === 'consumables') {
                $newStatus = 'Issued';
                $updateStock = $pdo->prepare("UPDATE assets SET current_stock = current_stock - 1, total_stock = total_stock - 1 WHERE asset_id = ?");
            } else {
                $newStatus = 'On Loan';
                $updateStock = $pdo->prepare("UPDATE assets SET current_stock = current_stock - 1 WHERE asset_id = ?");
            }
            $updateStock->execute([$asset_id]);

            // 3. Update the Request Status
            $stmtReq = $pdo->prepare("UPDATE borrowing_requests 
                                      SET status = ?, 
                                          tag_id = ?, 
                                          condition_note = ?,
                                          issued_date = NOW() 
                                      WHERE request_id = ?");
            $stmtReq->execute([$newStatus, $tag_id, $handover_note, $request_id]);

            // 4. Update the Tag Status
            $stmtTag = $pdo->prepare("UPDATE asset_tags SET status = ? WHERE tag_id = ?");
            $stmtTag->execute([$newStatus, $tag_id]);

            // --- ADDED: ADMIN LOGGING (Matches your 4-column schema) ---
            $admin_name = $_SESSION['full_name'];
            $log_action = "Admin $admin_name HANDED OVER {$details['asset_name']} (Tag: $tag_id) to {$details['full_name']}. Status: $newStatus.";
            
            $stmtLog = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_taken) VALUES (?, ?)");
            $stmtLog->execute([$_SESSION['user_id'], $log_action]);
            // --- END LOGGING ---

            $pdo->commit();

            // --- Ambil the unique tag nama ---
            $stmtActualTag = $pdo->prepare("SELECT unique_tag FROM asset_tags WHERE tag_id = ?");
            $stmtActualTag->execute([$tag_id]);
            $tagRow = $stmtActualTag->fetch();
            $display_tag = $tagRow ? $tagRow['unique_tag'] : "Unknown Tag";


            // 5. TRIGGER EMAIL NOTIFICATION
            $subject = ($newStatus === 'Issued') ? "Item Issued - Nuqtah System" : "Equipment Handover Confirmed - Nuqtah";

            $badgeColor = ($newStatus === 'Issued') ? "#E8F5E9" : "#E0F2F1";
            $badgeText = ($newStatus === 'Issued') ? "#2E7D32" : "#00796B";
            $statusLabel = ($newStatus === 'Issued') ? "Issued Permanently" : "On Loan";

            $message = "
                <div style='text-align: center; margin-bottom: 25px;'>
                    <span style='background-color: $badgeColor; color: $badgeText; padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>$statusLabel</span>
                    <h2 style='color: #333; margin-top: 15px; font-size: 22px;'>Handover Confirmation</h2>
                    <p style='color: #666; font-size: 15px;'>Hello <strong>{$details['full_name']}</strong>, your equipment request has been finalized.</p>
                </div>
                <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; font-size: 13px;'>Item</td>
                            <td style='padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 600; text-align: right;'>{$details['asset_name']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; font-size: 13px;'>Tag ID</td>
                            <td style='padding: 8px 0; color: #1e293b; font-size: 14px; font-weight: 600; text-align: right;'>
                                <code style='background-color: #f1f5f9; padding: 2px 4px; border-radius: 4px;'>$display_tag</code>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; font-size: 13px;'>Return Due</td>
                            <td style='padding: 8px 0; color: #b91c1c; font-size: 14px; font-weight: 600; text-align: right;'>" . 
                                ($newStatus === 'Issued' ? 'N/A' : date('d M Y', strtotime($details['return_date']))) . 
                            "</td>
                        </tr>
                    </table>
                </div>
                <div style='border-top: 1px dashed #e2e8f0; padding-top: 20px; font-size: 13px; color: #666;'>
                    <p style='margin: 0;'><strong>Note:</strong> " . ($handover_note ?: 'No specific condition notes recorded.') . "</p>
                </div>";

            sendNuqtahEmail($details['email'], $details['full_name'], $subject, $message);

            header("Location: ../view_requests.php?msg=issued");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log($e->getMessage());
            header("Location: ../view_requests.php?msg=error");
        }
    } else {
        header("Location: ../view_requests.php?msg=error");
    }
}
exit();