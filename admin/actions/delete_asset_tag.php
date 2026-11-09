<?php
session_start();
include '../../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$tag_id = $_POST['tag_id'] ?? null;

if ($tag_id) {
    // 1. Fetch tag details BEFORE deleting so we can log what was removed
    $infoStmt = $pdo->prepare("SELECT t.unique_tag, a.asset_name 
                               FROM asset_tags t 
                               JOIN assets a ON t.asset_id = a.asset_id 
                               WHERE t.tag_id = ?");
    $infoStmt->execute([$tag_id]);
    $tagData = $infoStmt->fetch();

    // 2. Proceed with deletion
    $stmt = $pdo->prepare("DELETE FROM asset_tags WHERE tag_id = ?");
    if ($stmt->execute([$tag_id])) {
        
        // 3. Log the activity if we found the tag data
        if ($tagData) {
            $admin_id = $_SESSION['user_id'];
            $log_msg = "Deleted serial tag [{$tagData['unique_tag']}] from asset: " . $tagData['asset_name'];
            logActivity($pdo, $admin_id, $log_msg);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing Tag ID']);
}