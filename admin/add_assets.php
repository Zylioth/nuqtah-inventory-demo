<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$message = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['asset_name'];
    $category = $_POST['category'];
    $initial_stock = $_POST['current_stock']; 
    
    $total_stock = $initial_stock;
    $current_stock = $initial_stock;
    
    $status = ($current_stock > 0) ? 'Available' : 'Out of Stock';
    
    $image_name = "";
    if (isset($_FILES['asset_image']) && $_FILES['asset_image']['error'] == 0) {
        $target_dir = "../assets/upload/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["asset_image"]["name"], PATHINFO_EXTENSION);
        $image_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $name) . "." . $file_ext;
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES["asset_image"]["tmp_name"], $target_file);
    }

    try {
        $sql = "INSERT INTO assets (asset_name, category, total_stock, current_stock, status, asset_image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$name, $category, $total_stock, $current_stock, $status, $image_name])) {
            
            // --- ACTIVITY LOG START ---
            $new_asset_id = $pdo->lastInsertId(); // Get the ID of the asset we just created
            $admin_id = $_SESSION['user_id'];
            $admin_name = $_SESSION['full_name'] ?? 'Admin'; // Fallback if name isn't in session
            
            $log_msg = "Admin $admin_name ADDED a new asset: $name (ID: #$new_asset_id) with initial stock of $initial_stock.";
            
            logActivity($pdo, $admin_id, $log_msg);
            // --- ACTIVITY LOG END ---

            $message = "success";
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
    <title>Add New Asset - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --teal-primary: #00796B; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .main-content { padding: 20px; min-height: 100vh; }
        .btn-teal { background-color: var(--teal-primary); color: white; border: none; }
        .btn-teal:hover { background-color: #004D40; color: white; }
        .form-card { max-width: 850px; margin: 0 auto; }
        .preview-img { max-width: 200px; height: auto; border: 2px solid #eee; border-radius: 12px; }
    </style>
</head>
<body>

<div class="main-content py-5">
    <div class="container">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="manage_assets.php" class="text-decoration-none text-muted">Inventory</a></li>
                        <li class="breadcrumb-item active fw-bold" style="color: var(--teal-primary);">Add New Asset</li>
                    </ol>
                </nav>
                <img src="../assets/img/logoNuqtah.png" alt="Nuqtah Logo" style="height: 45px; width: auto;">
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light p-3 rounded-3 me-3 text-teal">
                            <i class="bi bi-box-seam fs-3" style="color: var(--teal-primary);"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0">IT Assets Registration</h3>
                            <p class="text-muted mb-0">Add new IT assets or consumables into the system.</p>
                        </div>
                    </div>
                    
                    <hr class="mb-4 opacity-25">
                    
                    <?php if ($message == "success"): ?>
                        <div class="alert alert-success border-0 rounded-3 d-flex align-items-center py-3">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            <span>Asset added successfully! <a href="manage_assets.php" class="alert-link">Return to Inventory</a></span>
                        </div>
                    <?php elseif ($message == "error"): ?>
                        <div class="alert alert-danger border-0 rounded-3 py-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Failed to add asset. Please check the database.
                        </div>
                    <?php endif; ?>

                    <form action="add_assets.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Asset Name</label>
                            <input type="text" name="asset_name" class="form-control py-3 bg-light border-0 shadow-none" placeholder="e.g. HP 682 Black Ink" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Category</label>
                                <select name="category" class="form-select py-3 bg-light border-0 shadow-none" required>
                                    <option value="Laptops">Laptops</option>
                                    <option value="Projectors">Projectors</option>
                                    <option value="Accessories">Accessories</option>
                                    <option value="Consumables">Consumables</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Initial Quantity</label>
                                <input type="number" name="current_stock" class="form-control py-3 bg-light border-0 shadow-none" value="1" min="0" required>
                                <small class="text-muted">Sets both Total and Available stock.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Asset Image</label>
                            <div class="border rounded-3 p-4 bg-white text-center">
                                <div id="preview-container" class="mb-3 d-none">
                                    <img id="image-preview" src="#" alt="Preview" class="preview-img shadow-sm">
                                </div>
                                <input type="file" name="asset_image" id="asset_image" class="form-control mb-2" accept="image/*" onchange="previewImage(this)">
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Recommended: 800x600px (JPG, PNG, or WebP).</small>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-teal py-3 rounded-3 fw-bold fs-5">
                                <i class="bi bi-plus-lg me-2"></i> Save Asset to Database
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

<script>
function previewImage(input) {
    const container = document.getElementById('preview-container');
    const preview = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.classList.remove('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        container.classList.add('d-none');
    }
}
</script>

</body>
</html>