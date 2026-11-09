<?php
session_start();

// 1. Get the ID from the URL
$id = $_GET['id'] ?? null;

// 2. Simply unset the key if it exists
if ($id !== null && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

// 3. Go back to the review page
header("Location: ../cart.php");
exit();