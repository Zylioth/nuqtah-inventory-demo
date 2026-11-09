<?php
session_start();
include '../includes/db_connect.php';

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && $action && isset($_SESSION['cart'][$id])) {
    
    if ($action === 'plus') {
        // Fetch current stock to make sure they don't exceed it
        $stmt = $pdo->prepare("SELECT current_stock FROM assets WHERE asset_id = ?");
        $stmt->execute([$id]);
        $asset = $stmt->fetch();

        // Only increase if we haven't hit the stock limit
        if ($_SESSION['cart'][$id] < $asset['current_stock']) {
            $_SESSION['cart'][$id]++;
        }
    } 
    elseif ($action === 'minus') {
        // Decrease quantity, but don't go below 1
        if ($_SESSION['cart'][$id] > 1) {
            $_SESSION['cart'][$id]--;
        } else {
            // Optional: Remove item if they minus while at 1
            // unset($_SESSION['cart'][$id]);
        }
    }
}

// Redirect back to the cart page
header("Location: ../cart.php");
exit();