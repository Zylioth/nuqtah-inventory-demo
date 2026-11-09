<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";
$asset_id = $_GET['id'] ?? null;

if (!$asset_id) {
    header("Location: manage_assets.php");
    exit();
}

// 1. Fetch current data
$stmt = $pdo->prepare("SELECT * FROM assets WHERE asset_id = ?");
$stmt->execute([$asset_id]);
$asset = $stmt->fetch();

if (!$asset) {
    header("Location: manage_assets.php");
    exit();
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['asset_name'];
    $category = $_POST['category'];
    $total_stock = $_POST['total_stock'];
    $current_stock = $_POST['current_stock'];
    $status = ($current_stock > 0) ? 'Available' : 'Out of Stock';
    
    $old_image_name = $asset['asset_image']; 
    $image_name = $old_image_name; 

    if (isset($_FILES['asset_image']) && $_FILES['asset_image']['error'] == 0) {
        $target_dir = "../assets/upload/";
        $file_ext = pathinfo($_FILES["asset_image"]["name"], PATHINFO_EXTENSION);
        $new_image_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $name) . "." . $file_ext;
        
        if (move_uploaded_file($_FILES["asset_image"]["tmp_name"], $target_dir . $new_image_name)) {
            $image_name = $new_image_name; 
            
            if (!empty($old_image_name) && $old_image_name !== $new_image_name) {
                $old_file_path = $target_dir . $old_image_name;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
        }
    }

    try {
        $sql = "UPDATE assets SET asset_name = ?, category = ?, total_stock = ?, current_stock = ?, status = ?, asset_image = ? WHERE asset_id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$name, $category, $total_stock, $current_stock, $status, $image_name, $asset_id])) {
            
            // --- ACTIVITY LOG START ---
            $admin_id = $_SESSION['user_id'];
            $admin_name = $_SESSION['full_name'] ?? 'Admin';
            
            // Craft a detailed log message
            $log_msg = "Admin $admin_name UPDATED asset: $name (ID: #$asset_id). New Stock: $current_stock/$total_stock.";
            
            logActivity($pdo, $admin_id, $log_msg);
            // --- ACTIVITY LOG END ---

            $message = "success";
            
            // Refresh local data for the preview
            $asset['asset_name'] = $name;
            $asset['category'] = $category;
            $asset['total_stock'] = $total_stock;
            $asset['current_stock'] = $current_stock;
            $asset['asset_image'] = $image_name;
        } else {
            $message = "error";
        }
    } catch (PDOException $e) {
        $message = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Asset - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --teal-primary: #00796B; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .main-content { padding: 40px; min-height: 100vh; }
        .btn-teal { background-color: var(--teal-primary); color: white; border: none; }
        .btn-teal:hover { background-color: #004D40; color: white; }
        .form-card { max-width: 850px; margin: 0 auto; }
        .current-img-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; }
        .breadcrumb-item a { color: var(--teal-primary); text-decoration: none; }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="manage_assets.php">Inventory</a></li>
                        <li class="breadcrumb-item active fw-bold" aria-current="page">Edit Asset</li>
                    </ol>
                </nav>
                <img src="../assets/img/logoNuqtah.png" alt="Nuqtah Logo" style="height: 40px;">
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light p-3 rounded-3 me-3 text-teal">
                            <i class="bi bi-pencil-square fs-3" style="color: var(--teal-primary);"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0">Edit Equipment Details</h3>
                            <p class="text-muted mb-0">Modify the asset information for <strong><?php echo htmlspecialchars($asset['asset_name']); ?></strong></p>
                        </div>
                    </div>
                    
                    <hr class="mb-4 opacity-25">
                    
                    <?php if ($message == "success"): ?>
                        <div class="alert alert-success border-0 rounded-3 d-flex align-items-center py-3 mb-4">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            <span>Changes saved successfully! <a href="manage_assets.php" class="alert-link">Back to Inventory</a></span>
                        </div>
                    <?php elseif ($message == "error"): ?>
                        <div class="alert alert-danger border-0 rounded-3 py-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Failed to update asset.
                        </div>
                    <?php endif; ?>

                    <form action="edit_asset.php?id=<?php echo $asset_id; ?>" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Asset Name</label>
                            <input type="text" name="asset_name" class="form-control py-3 bg-light border-0 shadow-none" value="<?php echo htmlspecialchars($asset['asset_name']); ?>" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Category</label>
                                <select name="category" class="form-select py-3 bg-light border-0 shadow-none" required>
                                    <?php 
                                    $cats = ["Laptops", "Projectors", "Accessories", "Consumables", "Others"];
                                    foreach($cats as $cat) {
                                        $selected = ($asset['category'] == $cat) ? "selected" : "";
                                        echo "<option value='$cat' $selected>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Available Stock</label>
                                <input type="number" name="current_stock" class="form-control py-3 bg-light border-0 shadow-none" value="<?php echo $asset['current_stock']; ?>" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Total Owned</label>
                                <input type="number" name="total_stock" class="form-control py-3 bg-light border-0 shadow-none" value="<?php echo $asset['total_stock']; ?>" min="0" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Update Asset Image</label>
                            <div class="d-flex align-items-center bg-light p-4 rounded-3">
                                <?php if($asset['asset_image']): ?>
                                    <img src="../assets/upload/<?php echo $asset['asset_image']; ?>" class="current-img-preview me-4 border shadow-sm">
                                <?php else: ?>
                                    <div class="current-img-preview me-4 border bg-white d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <input type="file" name="asset_image" class="form-control mb-1" accept="image/*">
                                    <small class="text-muted">Upload a new image to replace the current one.</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-teal py-3 rounded-3 fw-bold fs-5">
                                Save Changes
                            </button>
                            <a href="manage_assets.php" class="btn btn-light py-3 border-0">Cancel and Return</a>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted small mt-4">Nuqtah IT Inventory System &copy; 2026</p>
        </div>
    </div>
</div>

</body>
</html>