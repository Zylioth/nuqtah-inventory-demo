<?php
session_start();
include 'includes/db_connect.php';
// Include the mail helper
include_once 'includes/mail_helper.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: inventory_list.php");
    exit();
}

// Fetch items in the cart
$cart_ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM assets WHERE asset_id IN ($placeholders)");
$stmt->execute($cart_ids);
$cart_items = $stmt->fetchAll();

// Check if the cart contains only consumables
$onlyConsumables = true;
foreach ($cart_items as $item) {
    if (strtolower($item['category']) !== 'consumables') {
        $onlyConsumables = false;
        break;
    }
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['full_name'];
    $user_email = $_SESSION['email'];
    // Capture return and pickup dates from form
    $form_return_date = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
    $form_pickup_date = !empty($_POST['pickup_date']) ? $_POST['pickup_date'] : null;

    // Basic validation: pickup date cannot be in the past
    if ($form_pickup_date) {
        $today = date('Y-m-d');
        if ($form_pickup_date < $today) {
            throw new Exception('Pickup date cannot be in the past.');
        }
    }

    try {
        $pdo->beginTransaction();
        
        // Include pickup_date; if your DB doesn't have this column, add it or let me know
        $insert = $pdo->prepare("INSERT INTO borrowing_requests (user_id, asset_id, quantity, pickup_date, return_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        
        $emailItemList = "<ul>"; 

        foreach ($_SESSION['cart'] as $asset_id => $qty) {
            $check_stock = $pdo->prepare("SELECT asset_name, current_stock, category FROM assets WHERE asset_id = ?");
            $check_stock->execute([$asset_id]);
            $asset = $check_stock->fetch();

            if ($qty > $asset['current_stock']) {
                throw new Exception("Sorry, only " . $asset['current_stock'] . " units of " . $asset['asset_name'] . " are available.");
            }

            // If item is a consumable, it doesn't need a return date in the database
            $actualReturnDate = (strtolower($asset['category']) === 'consumables') ? null : $form_return_date;

            // For consumables pickup_date still applies; allow null if not provided
            $actualPickupDate = $form_pickup_date ?: null;

            $insert->execute([$user_id, $asset_id, $qty, $actualPickupDate, $actualReturnDate]);
            
            $emailItemList .= "<li>" . htmlspecialchars($asset['asset_name']) . " (Qty: $qty)</li>";
        }

        $emailItemList .= "</ul>";
        $pdo->commit();

        // Prepare dates for emails
        $displayReturnDate = $onlyConsumables ? "N/A (Consumables)" : ($form_return_date ?? "Not specified");
        $displayPickupDate = $form_pickup_date ?? date('Y-m-d');

        // --- EMAIL NOTIFICATION LOGIC ---
        $userSubject = "Request Received - Nuqtah Inventory";
        $userBody = "
            <p>Hello <b>$user_name</b>,</p>
            <p>Your request for the following items has been submitted:</p>
            $emailItemList
            <p><b>Expected Pickup:</b> $displayPickupDate</p>
            <p><b>Expected Return:</b> $displayReturnDate</p>
            <p><b>Status:</b> Pending Approval</p>
            <p>Please wait for an official approval email before collecting from the ICT Department.</p>";
        
        sendNuqtahEmail($user_email, $user_name, $userSubject, $userBody);

        $adminStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE role = 'Admin' AND account_status = 'Active'");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll();

        foreach ($admins as $admin) {
            $adminSubject = "ACTION REQUIRED: New Request from $user_name";
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/Nuqtah_IT', '/');
            $adminBody = "
                <h3 style='color: #00796B;'>New Borrow Request</h3>
                <p><b>From:</b> $user_name ($user_email)</p>
                <p><b>Items:</b></p>
                $emailItemList
                <p><b>Expected Pickup:</b> $displayPickupDate</p>
                <p><b>Required Until:</b> $displayReturnDate</p>
                <br>
                <a href='{$appUrl}/admin/view_requests.php' 
                   style='background: #00796B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Review Request Now
                </a>";
            sendNuqtahEmail($admin['email'], $admin['full_name'], $adminSubject, $adminBody);
        }

        unset($_SESSION['cart']); 
        header("Location: inventory_list.php?msg=submitted");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Request - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php include 'includes/header.php'; ?>
<?php include 'includes/users_sidebar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger rounded-4 mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold m-0">Review Your Request</h2>
                    <a href="inventory_list.php" class="btn btn-outline-secondary btn-sm rounded-pill">Add More Items</a>
                </div>

                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Details</th>
                            <th>Quantity</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): 
                                $id = $item['asset_id'];
                                $qty = $_SESSION['cart'][$id];
                                $isConsumable = (strtolower($item['category']) === 'consumables');
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($item['asset_name']); ?></div>
                                    <span class="badge <?php echo $isConsumable ? 'bg-info text-dark' : 'bg-secondary'; ?> small">
                                        <?php echo ucfirst($item['category']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="actions/update_cart.php?id=<?php echo $id; ?>&action=minus" class="btn btn-sm btn-outline-secondary">-</a>
                                        <span class="mx-3 fw-bold"><?php echo $qty; ?></span>
                                        <a href="actions/update_cart.php?id=<?php echo $id; ?>&action=plus" class="btn btn-sm btn-outline-secondary">+</a>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="actions/remove_from_cart.php?id=<?php echo $id; ?>" class="text-danger">
                                        <i class="bi bi-trash"></i>
                                    </a>                                
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="POST" class="mt-4 pt-3 border-top">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expected Pickup Date</label>
                            <input type="date" name="pickup_date" class="form-control rounded-3" required 
                                   min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                            <small class="text-muted">Select when you plan to pick up the items (same-day allowed).</small>
                        </div>

                        <div class="col-md-6" id="return_date_section" style="<?php echo $onlyConsumables ? 'display:none;' : ''; ?>">
                            <label class="form-label fw-bold">Expected Return Date</label>
                            <input type="date" name="return_date" class="form-control rounded-3" 
                                   <?php echo !$onlyConsumables ? 'required' : ''; ?> 
                                   min="<?php echo date('Y-m-d'); ?>">
                            <small class="text-muted">Please select when you plan to return the durable items.</small>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-teal w-100 py-3 rounded-pill fw-bold text-white" style="background-color: #00796B;">
                            Confirm & Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>