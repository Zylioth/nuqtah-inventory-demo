<?php
session_start();
include '../../includes/db_connect.php';

// Set header to return JSON responses for our AJAX callers
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

$tag_id = $_POST['tag_id'] ?? null;
$asset_id = $_POST['asset_id'] ?? null;
$status = $_POST['status'] ?? null;
$unique_tag = $_POST['unique_tag'] ?? 'Unknown';

// Validate allowed status types
$allowed_statuses = ['Available', 'Maintenance', 'Damaged', 'On Loan'];
if (!$tag_id || !$asset_id || !$status || !in_array($status, $allowed_statuses)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid parameters or status.']));
}

try {
    $pdo->beginTransaction();

    // 1. Fetch the previous status of the tag to calculate stock adjustment
    $stmtOld = $pdo->prepare("SELECT status FROM asset_tags WHERE tag_id = ?");
    $stmtOld->execute([$tag_id]);
    $old_status = $stmtOld->fetchColumn();

    if ($old_status === false) {
        throw new Exception("Asset tag not found in system.");
    }

    // Prevent manual adjustment if item is currently on loan
    if ($old_status === 'On Loan' && $status !== 'On Loan') {
        throw new Exception("This tag is currently On Loan to a student. Please process its return through the Borrowing panel first.");
    }

    // 2. Update the asset tag's physical status
    $stmtUpdate = $pdo->prepare("UPDATE asset_tags SET status = ? WHERE tag_id = ?");
    $stmtUpdate->execute([$status, $tag_id]);

    // 3. Compute Parent Asset Stock Sync (Upgrade A)
    // Stock is only affected when transitioning to/from 'Available' status
    if ($old_status !== 'Available' && $status === 'Available') {
        // Tag is restored/repaired: Increase parent's available stock
        $stmtStock = $pdo->prepare("UPDATE assets SET current_stock = current_stock + 1 WHERE asset_id = ?");
        $stmtStock->execute([$asset_id]);
    } elseif ($old_status === 'Available' && $status !== 'Available') {
        // Tag is damaged or sent to maintenance: Decrease parent's available stock (keep stock >= 0)
        $stmtStock = $pdo->prepare("UPDATE assets SET current_stock = GREATEST(0, current_stock - 1) WHERE asset_id = ?");
        $stmtStock->execute([$asset_id]);
    }

    // 4. Log the movement to admin_logs (Upgrade D)
    $admin_name = $_SESSION['full_name'];
    $log_action = "Admin $admin_name modified tag $unique_tag condition from '$old_status' to '$status'.";
    
    $stmtLog = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_taken) VALUES (?, ?)");
    $stmtLog->execute([$_SESSION['user_id'], $log_action]);

    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Status updated successfully', 
        'old_status' => $old_status, 
        'new_status' => $status
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}