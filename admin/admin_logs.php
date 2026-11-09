<?php
session_start();
// Go back one folder to find the connection
include '../includes/db_connect.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// 1. Pagination & Search Parameters Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 25; // 25 logs per page for optimal scaling
$offset = ($page - 1) * $limit;

// 2. Build Count and Fetch Queries
$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(l.action_taken LIKE :search1 OR u.username LIKE :search2)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM admin_logs l JOIN users u ON l.admin_id = u.user_id $where_sql";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_records / $limit));

if ($page > $total_pages) {
    $page = $total_pages;
}

// Fetch logs with limit and offset
$query = "SELECT l.*, u.username 
          FROM admin_logs l 
          JOIN users u ON l.admin_id = u.user_id 
          $where_sql
          ORDER BY l.timestamp DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Log | Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { 
            --teal-primary: #00796B; 
            --teal-dark: #004D40; 
            --teal-light: rgba(0, 121, 107, 0.08);
        }
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .audit-container { max-width: 1200px; margin: 40px auto; }
        
        /* Premium Solid Design Tokens (Flat) */
        .card { 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
        }
        
        .table thead th { 
            background-color: #f8f9fa; 
            color: #555; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 1.1rem 1rem;
            border-bottom: 2px solid #edf2f7;
        }
        
        .admin-user { color: var(--teal-primary); font-weight: 600; }
        .action-text { color: #2d3748; font-size: 0.9rem; }
        
        /* Highlighting Badges for quick auditing scans */
        .log-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .log-badge-danger { background-color: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
        .log-badge-warning { background-color: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2; }
        .log-badge-success { background-color: #E8F5E9; color: #1B5E20; border: 1px solid #C8E6C9; }
        .log-badge-info { background-color: #E3F2FD; color: #0D47A1; border: 1px solid #BBDEFB; }

        .pagination .page-item.active .page-link {
            background-color: var(--teal-primary);
            border-color: var(--teal-primary);
        }
        .pagination .page-link {
            color: var(--teal-primary);
        }
        .pagination .page-link:hover {
            background-color: rgba(0, 121, 107, 0.1);
        }

        /* Verified Signature Frame for Official Reporting */
        .certified-block {
            display: none;
            margin-top: 80px;
            border-top: 2px solid #ddd;
            padding-top: 20px;
        }

        @media print {
            .no-print, .pagination-container { display: none !important; }
            body { background-color: white; color: black; }
            .audit-container { margin: 0; max-width: 100%; padding: 15px; }
            .card { box-shadow: none; border: 1px solid #e2e8f0; }
            .certified-block { display: flex !important; }
        }
    </style>
</head>
<body>

<div class="container audit-container">
    <!-- Action Header Panel (Hidden on Print out) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 no-print">
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 bg-white text-dark text-decoration-none border shadow-sm">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
        <div class="text-md-end">
            <h3 class="fw-bold mb-0">System Audit Log</h3>
            <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.75rem;">Security Administration Mode</small>
        </div>
        <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 shadow-sm">
            <i class="bi bi-printer-fill me-2"></i>Print Report
        </button>
    </div>

    <!-- Official Header Panel (Visible only when printed) -->
    <div class="d-none d-print-block border-bottom border-3 pb-3 mb-4">
        <div class="row align-items-center">
            <div class="col-8">
                <h3 class="fw-bold text-uppercase mb-1" style="color: var(--teal-dark);">Nuqtah Security Audit Log</h3>
                <p class="text-muted small mb-0">ITQSHHB Inventory Tracking and Operations Security Journal</p>
            </div>
            <div class="col-4 text-end">
                <p class="small mb-1"><strong>Ref:</strong> #LOGS-<?php echo date('Ymd-Hi'); ?></p>
                <p class="small text-muted mb-0"><strong>Generated:</strong> <?php echo date('d M Y, h:i A'); ?></p>
            </div>
        </div>
    </div>

    <!-- Search/Filter Engine -->
    <div class="card rounded-4 mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 border-end-0 ps-0" 
                               placeholder="Search logs by keyword, admin username, action details..." 
                               value="<?php echo htmlspecialchars($search); ?>" style="height: 42px;">
                        <?php if ($search !== ''): ?>
                            <a href="admin_logs.php" class="btn btn-white bg-white border-start-0 border-end-0 text-muted d-flex align-items-center px-3" title="Clear Filters">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-teal px-4" style="background-color: var(--teal-primary); color: white;">Filter Logs</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Filter Indicators -->
    <?php if ($search !== ''): ?>
        <div class="mb-3 ms-2 no-print">
            <span class="text-muted small fw-bold text-uppercase">Active Search Query:</span>
            <span class="badge bg-white text-dark border rounded-pill py-2 px-3 ms-2 shadow-sm d-inline-flex align-items-center gap-2">
                "<?php echo htmlspecialchars($search); ?>"
                <a href="admin_logs.php" class="text-danger"><i class="bi bi-x-circle-fill"></i></a>
            </span>
        </div>
    <?php endif; ?>

    <!-- Audit Log Records Grid Card -->
    <div class="card overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" width="20%">Timestamp</th>
                            <th width="20%">Administrator</th>
                            <th width="45%">Activity Details</th>
                            <th class="text-end pe-4 no-print" width="15%">Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <span class="text-muted d-block small mb-1"><?php echo date('D, d M Y', strtotime($log['timestamp'])); ?></span>
                                    <span class="fw-bold text-dark"><i class="bi bi-clock me-1 text-muted"></i> <?php echo date('h:i A', strtotime($log['timestamp'])); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center admin-user">
                                        <i class="bi bi-person-badge-fill me-2 opacity-75"></i>
                                        <code><?php echo htmlspecialchars($log['username']); ?></code>
                                    </div>
                                </td>
                                <td class="action-text py-3 text-dark font-monospace" style="font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($log['action_taken']); ?>
                                </td>
                                <td class="text-end pe-4 no-print">
                                    <?php
                                        // Dynamic text parser to highlight critical security events (Upgrade)
                                        $action = strtolower($log['action_taken']);
                                        if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false || strpos($action, 'demote') !== false) {
                                            echo '<span class="log-badge log-badge-danger"><i class="bi bi-shield-slash me-1"></i>High Risk</span>';
                                        } elseif (strpos($action, 'maintenance') !== false || strpos($action, 'status') !== false || strpos($action, 'repaired') !== false) {
                                            echo '<span class="log-badge log-badge-warning"><i class="bi bi-tools me-1"></i>Hardware</span>';
                                        } elseif (strpos($action, 'add') !== false || strpos($action, 'create') !== false) {
                                            echo '<span class="log-badge log-badge-success"><i class="bi bi-plus-circle me-1"></i>Creation</span>';
                                        } else {
                                            echo '<span class="log-badge log-badge-info"><i class="bi bi-info-circle me-1"></i>Standard</span>';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-x display-6 d-block mb-3"></i>
                                    No transaction logs match your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Administrative Certified Signatures Frame (Visible only on print out) -->
    <div class="row certified-block">
        <div class="col-6">
            <p class="small mb-5 text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">Compiled By (ICT Registrar):</p>
            <div style="border-bottom: 1px solid #777; width: 250px; margin-top: 50px;"></div>
            <p class="small mt-2 mb-0">Signature & Official Stamp</p>
        </div>
        <div class="col-6 text-end d-flex flex-column align-items-end">
            <p class="small mb-5 text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">Reviewed & Verified By:</p>
            <div style="border-bottom: 1px solid #777; width: 250px; margin-top: 50px;"></div>
            <p class="small mt-2 mb-0">ICT Department Coordinator</p>
        </div>
    </div>

    <!-- Server-Side Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <div class="row mt-4 pagination-container">
        <div class="col-12 d-flex flex-column flex-sm-row align-items-center justify-content-between">
            <div class="text-muted small mb-3 mb-sm-0">
                Showing entries <?php echo ($offset + 1); ?> to <?php echo min($offset + count($logs), $total_records); ?> of <?php echo $total_records; ?> records
            </div>
            <nav aria-label="Logs pagination">
                <ul class="pagination mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>" tabindex="-1">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>

    <p class="text-center mt-4 text-muted small no-print">
        End of system report. Generated on <?php echo date('d M Y, h:i A'); ?>
    </p>
</div>

</body>
</html>