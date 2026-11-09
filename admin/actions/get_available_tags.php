<?php
include '../../includes/db_connect.php';
$asset_id = $_GET['asset_id'] ?? 0;

$stmt = $pdo->prepare("SELECT tag_id, unique_tag FROM asset_tags WHERE asset_id = ? AND status = 'Available'");
$stmt->execute([$asset_id]);
$tags = $stmt->fetchAll();

if ($tags) {
    echo '<option value="" disabled selected>Select a specific tag...</option>';
    foreach ($tags as $tag) {
        // IMPORTANT: value must be the numeric tag_id
        echo "<option value='{$tag['tag_id']}'>{$tag['unique_tag']}</option>";
    }
} else {
    echo '<option disabled>No items available in stock</option>';
}