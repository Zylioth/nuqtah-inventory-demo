<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Default filter values
$date_from = $_GET['date_from'] ?? date('Y-m-01'); 
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'borrowing';

// 1. Build the Query based on filters
if ($report_type == 'borrowing') {
    $sql = "SELECT b.*, u.full_name, u.user_id as student_id, a.asset_name, a.category 
            FROM borrowing_requests b
            JOIN users u ON b.user_id = u.user_id
            JOIN assets a ON b.asset_id = a.asset_id
            WHERE b.request_date BETWEEN :from AND :to
            ORDER BY b.request_date DESC";
} elseif ($report_type == 'logs') {
    $sql = "SELECT l.*, u.full_name 
            FROM admin_logs l
            JOIN users u ON l.admin_id = u.user_id
            WHERE l.timestamp BETWEEN :from AND :to
            ORDER BY l.timestamp DESC";
} else {
    $sql = "SELECT asset_id, asset_name, category, current_stock, total_stock, status FROM assets ORDER BY category ASC, asset_name ASC";
}

$stmt = $pdo->prepare($sql);
if ($report_type == 'borrowing' || $report_type == 'logs') {
    $stmt->execute(['from' => $date_from . ' 00:00:00', 'to' => $date_to . ' 23:59:59']);
} else {
    $stmt->execute();
}
$data = $stmt->fetchAll();

// 2. Dynamic Contextual Summary Logic
$total_count = count($data);

// Borrowing Summary Metrics
$late_count = 0;
$active_loans = 0;

// Asset Summary Metrics
$low_stock_assets = 0;
$healthy_stock_assets = 0;

// Log Summary Metrics
$critical_actions = 0;

if ($report_type == 'borrowing') {
    foreach ($data as $row) {
        $d = date('Y-m-d', strtotime($row['return_date']));
        $a = !empty($row['actual_return_date']) ? date('Y-m-d', strtotime($row['actual_return_date'])) : null;
        $t = date('Y-m-d');

        if (($row['status'] == 'On Loan' && $t > $d) || ($a && $a > $d)) {
            $late_count++;
        }
        if ($row['status'] == 'On Loan') {
            $active_loans++;
        }
    }
} elseif ($report_type == 'assets') {
    foreach ($data as $row) {
        if ($row['current_stock'] <= 5) {
            $low_stock_assets++;
        } else {
            $healthy_stock_assets++;
        }
    }
} elseif ($report_type == 'logs') {
    foreach ($data as $row) {
        $action = strtolower($row['action_taken']);
        // Identify administrative risk operations (deletes, updates, suspensions)
        if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false || strpos($action, 'suspend') !== false || strpos($action, 'update') !== false) {
            $critical_actions++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuqtah - Audit Report</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root { --teal-primary: #00796B; --teal-dark: #004D40; --teal-light: rgba(0, 121, 107, 0.08); }
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; font-size: 14px; }
        
        /* Fixed responsive structural layouts */
        .main-content { transition: all 0.3s; padding: 20px; }
        
        @media (min-width: 992px) {
            .main-content { margin-left: 260px; padding: 30px; }
        }

        /* Paper Sheet Layout */
        .report-paper {
            background: white; padding: 60px; min-height: 297mm; max-width: 210mm;
            margin: 20px auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position: relative;
            border-radius: 8px; border: 1px solid #e2e8f0;
        }

        .btn-teal { background-color: var(--teal-primary) !important; color: white !important; border: none; }
        .btn-teal:hover { background-color: var(--teal-dark) !important; }

        .audit-table th { 
            background-color: #f8f9fa !important; font-size: 0.75rem; 
            text-transform: uppercase; border-top: 2px solid #333; padding: 12px;
            color: #495057; font-weight: 700;
        }
        .audit-table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid #edf2f7; }
        
        /* Signature Area Rules */
        .signature-block {
            margin-top: 80px;
            border-top: 1px solid #e2e8f0;
            padding-top: 30px;
        }
        .signature-line {
            border-bottom: 1px dashed #cbd5e0;
            width: 200px;
            margin: 40px auto 10px auto;
        }

        @media print {
            .d-print-none, .sidebar { display: none !important; }
            body { background-color: #ffffff !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .report-paper { margin: 0; box-shadow: none; max-width: 100%; padding: 40px; border: none; }
            .audit-table th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid d-print-none mb-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">REPORT TYPE</label>
                        <select name="report_type" class="form-select border-0 bg-light rounded-3">
                            <option value="borrowing" <?php echo $report_type == 'borrowing' ? 'selected' : ''; ?>>Borrowing Activity</option>
                            <option value="assets" <?php echo $report_type == 'assets' ? 'selected' : ''; ?>>Asset Inventory</option>
                            <option value="logs" <?php echo $report_type == 'logs' ? 'selected' : ''; ?>>System Activity Log</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">FROM DATE</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-control border-0 bg-light rounded-3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">TO DATE</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-control border-0 bg-light rounded-3">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-teal w-100 rounded-pill fw-bold py-2">Generate Report</button>
                        <button type="button" onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 py-2">
                            <i class="bi bi-printer-fill"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="report-paper">
        <div class="report-header d-flex justify-content-between align-items-start border-bottom border-3 pb-3 mb-4">
            <div>
                <!-- Retained consistent logo placement -->
                <img src="/Nuqtah_IT/assets/img/logoNuqtah.png" height="60" alt="Nuqtah Logo" class="mb-2">
                <p class="text-muted small mb-0">ICT Department Inventory System<br>ITQSHHB, Brunei Darussalam</p>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-dark mb-1 text-uppercase">
                    <?php 
                        if($report_type == 'logs') echo "System Activity Log";
                        elseif($report_type == 'assets') echo "Asset Inventory Report";
                        else echo "Borrowing Audit Report";
                    ?>
                </h4>
                <p class="mb-0 small text-muted"><strong>Ref:</strong> #AUD-<?php echo date('Ymd-Hi'); ?></p>
                <p class="mb-0 small text-muted"><strong>Period:</strong> <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?></p>
            </div>
        </div>

        <!-- Fully dynamic, context-aware summary logic metrics block -->
        <div class="row g-3 mb-5">
            <div class="col-4">
                <div class="p-3 border rounded-4 bg-light text-center">
                    <p class="text-muted small text-uppercase fw-bold mb-1">
                        <?php 
                            if ($report_type == 'borrowing') echo 'Total Requests';
                            elseif ($report_type == 'assets') echo 'Catalog Size';
                            else echo 'Total Events';
                        ?>
                    </p>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo $total_count; ?></h2>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 border rounded-4 bg-light text-center">
                    <p class="text-muted small text-uppercase fw-bold mb-1">
                        <?php 
                            if ($report_type == 'borrowing') echo 'Late / Overdue';
                            elseif ($report_type == 'assets') echo 'Low Stock Alerts';
                            else echo 'Critical Actions';
                        ?>
                    </p>
                    <h2 class="fw-bold mb-0 <?php 
                        if ($report_type == 'borrowing') echo ($late_count > 0) ? 'text-danger' : 'text-dark';
                        elseif ($report_type == 'assets') echo ($low_stock_assets > 0) ? 'text-warning' : 'text-dark';
                        else echo ($critical_actions > 0) ? 'text-danger' : 'text-dark';
                    ?>">
                        <?php 
                            if ($report_type == 'borrowing') echo $late_count;
                            elseif ($report_type == 'assets') echo $low_stock_assets;
                            else echo $critical_actions;
                        ?>
                    </h2>
                </div>
            </div>
            <div class="col-4">
                <div class="p-3 border rounded-4 bg-light text-center">
                    <p class="text-muted small text-uppercase fw-bold mb-1">
                        <?php 
                            if ($report_type == 'borrowing') echo 'Active Loans';
                            elseif ($report_type == 'assets') echo 'Healthy Items';
                            else echo 'Audit Status';
                        ?>
                    </p>
                    <h4 class="fw-bold mb-0 py-1 <?php 
                        if ($report_type == 'borrowing') echo 'text-teal';
                        elseif ($report_type == 'assets') echo 'text-success';
                        else echo 'text-success';
                    ?>">
                        <?php 
                            if ($report_type == 'borrowing') echo $active_loans . ' Units';
                            elseif ($report_type == 'assets') echo $healthy_stock_assets . ' Items';
                            else echo 'VERIFIED';
                        ?>
                    </h4>
                </div>
            </div>
        </div>

        <table class="table audit-table mt-4">
            <thead>
                <?php if ($report_type == 'borrowing'): ?>
                    <tr>
                        <th width="15%">REF ID</th>
                        <th width="25%">BORROWER</th>
                        <th width="20%">ASSET</th>
                        <th width="15%">DEADLINE</th>
                        <th width="15%">ACTUAL RETURN</th>
                        <th width="10%" class="text-end">STATUS</th>
                    </tr>
                <?php elseif ($report_type == 'logs'): ?>
                    <tr>
                        <th width="15%">LOG ID</th>
                        <th width="20%">TIMESTAMP</th>
                        <th width="20%">ADMIN</th>
                        <th colspan="3">ACTION TAKEN</th>
                    </tr>
                <?php else: ?>
                    <tr>
                        <th width="15%">ASSET ID</th>
                        <th width="35%">NAME</th>
                        <th width="20%">CATEGORY</th>
                        <th width="15%">STOCK</th>
                        <th colspan="2" class="text-end">STATUS</th>
                    </tr>
                <?php endif; ?>
            </thead>
            
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php if ($report_type == 'borrowing'): ?>
                                <td class="text-muted small">#REQ-<?php echo $row['request_id']; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">ID: <?php echo htmlspecialchars($row['student_id']); ?></div>
                                </td>
                                <td class="text-dark fw-semibold"><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                <td class="small text-secondary"><?php echo date('d M Y', strtotime($row['return_date'])); ?></td>
                                
                                <td class="small">
                                    <?php 
                                    if (!empty($row['actual_return_date'])) {
                                        $deadline_date = date('Y-m-d', strtotime($row['return_date']));
                                        $actual_date = date('Y-m-d', strtotime($row['actual_return_date']));
                                        
                                        $isLate = ($actual_date > $deadline_date);
                                        $color = $isLate ? 'text-danger fw-bold' : 'text-success';
                                        
                                        echo "<span class='$color'>" . date('d M Y', strtotime($row['actual_return_date'])) . "</span>";
                                    } else { 
                                        echo '<span class="text-muted">—</span>'; 
                                    }
                                    ?>
                                </td>

                                <td class="text-end fw-bold small">
                                    <?php 
                                        $deadline_date = date('Y-m-d', strtotime($row['return_date']));
                                        $actual_date = !empty($row['actual_return_date']) ? date('Y-m-d', strtotime($row['actual_return_date'])) : null;
                                        $today_date = date('Y-m-d');

                                        if ($row['status'] == 'On Loan') {
                                            if ($today_date > $deadline_date) {
                                                echo '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2">OVERDUE</span>';
                                            } else {
                                                echo '<span class="text-primary">ON LOAN</span>';
                                            }
                                        } 
                                        elseif ($row['status'] == 'Returned') {
                                            if ($actual_date && $actual_date > $deadline_date) {
                                                echo '<span class="text-danger">LATE</span>';
                                            } else {
                                                echo '<span class="text-success">RETURNED</span>';
                                            }
                                        } 
                                        else {
                                            echo '<span class="text-muted">' . strtoupper($row['status']) . '</span>';
                                        }
                                    ?>
                                </td>

                            <?php elseif ($report_type == 'logs'): ?>
                                <td class="text-muted small">#LOG-<?php echo $row['log_id']; ?></td>
                                <td class="small text-secondary"><?php echo date('d/m/y H:i', strtotime($row['timestamp'])); ?></td>
                                <td class="fw-bold text-dark small"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td colspan="3" class="small text-secondary"><?php echo htmlspecialchars($row['action_taken']); ?></td>
                            
                            <?php else: ?>
                                <td class="text-muted small">#AST-<?php echo $row['asset_id']; ?></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                <td class="small"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                <td class="text-dark fw-semibold"><?php echo $row['current_stock']; ?> <span class="text-muted">/ <?php echo $row['total_stock']; ?></span></td>
                                <td colspan="2" class="text-end small fw-bold text-uppercase">
                                    <?php 
                                        if ($row['current_stock'] == 0) {
                                            echo '<span class="text-danger">OUT OF STOCK</span>';
                                        } elseif ($row['current_stock'] <= 5) {
                                            echo '<span class="text-warning">LOW STOCK</span>';
                                        } else {
                                            echo '<span class="text-success">AVAILABLE</span>';
                                        }
                                    ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Formal Certification Sign-off Panel for print verification -->
        <div class="signature-block">
            <div class="row text-center mt-5">
                <div class="col-6">
                    <p class="small text-muted mb-0">Prepared By</p>
                    <div class="signature-line"></div>
                    <p class="fw-bold mb-1 small text-dark"><?php echo htmlspecialchars($_SESSION['username'] ?? 'System Administrator'); ?></p>
                    <p class="small text-muted" style="font-size: 0.7rem;">ICT Admin, ITQSHHB</p>
                </div>
                <div class="col-6">
                    <p class="small text-muted mb-0">Verified & Approved By</p>
                    <div class="signature-line"></div>
                    <p class="fw-bold mb-1 small text-dark">Head of ICT Department</p>
                    <p class="small text-muted" style="font-size: 0.7rem;">ITQSHHB, Brunei Darussalam</p>
                </div>
            </div>
            <div class="text-center mt-5">
                <small class="text-muted" style="font-size: 0.65rem;">
                    This document was dynamically generated by the Nuqtah IT Inventory System on <?php echo date('d M Y, h:i A'); ?>. Page 1 of 1.
                </small>
            </div>
        </div>
    </div>
</div>
</body>
</html>