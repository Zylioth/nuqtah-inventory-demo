<?php
session_start();
$id = $_POST['asset_id'];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// If item exists, increase quantity; if not, set to 1
if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]++;
} else {
    $_SESSION['cart'][$id] = 1;
}

header("Location: ../inventory_list.php?msg=added");
exit();