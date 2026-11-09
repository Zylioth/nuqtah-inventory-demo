<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized access");
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // 1. Fetch the image filename before deleting the record
        $stmt = $pdo->prepare("SELECT asset_image FROM assets WHERE asset_id = ?");
        $stmt->execute([$id]);
        $asset = $stmt->fetch();

        if ($asset && !empty($asset['asset_image'])) {
            $file_path = "../assets/upload/" . $asset['asset_image'];
            // 2. Check if file exists on the server, then delete it
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // 3. Now delete the record from the database
        $stmt = $pdo->prepare("DELETE FROM assets WHERE asset_id = ?");
        $stmt->execute([$id]);
        
        header("Location: manage_assets.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        // Log error if needed: error_log($e->getMessage());
        header("Location: manage_assets.php?msg=error");
        exit();
    }
} else {
    // If no ID is provided, just go back
    header("Location: manage_assets.php");
    exit();
}