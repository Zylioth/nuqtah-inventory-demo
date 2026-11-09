<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Low stock threshold definition
$low_stock_threshold = 5;
$itemsPerPage = 20;

// Fetch active search/filter options from the URL to enable server-side processing
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Base queries for counting and fetching assets with dynamic search constraints
$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(asset_name LIKE :search1 OR category LIKE :search2 OR asset_id LIKE :search3)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
}

if ($category_filter !== '' && $category_filter !== 'All Categories') {
    $where_clauses[] = "category = :category_filter";
    $params[':category_filter'] = $category_filter;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get filtered total count for pagination
$countQuery = "SELECT COUNT(*) FROM assets $where_sql";
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$totalAssets = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalAssets / $itemsPerPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $itemsPerPage;

// Fetch paginated assets based on searching/filtering
$query = "SELECT * FROM assets $where_sql ORDER BY category ASC, asset_name ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$assets = $stmt->fetchAll();

// Fetch ALL unique categories from database (global, unaffected by pagination)
$catStmt = $pdo->query("SELECT DISTINCT category FROM assets WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch global low stock count (independent of active pagination limits)
$lowStockStmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE current_stock <= :threshold");
$lowStockStmt->bindValue(':threshold', $low_stock_threshold, PDO::PARAM_INT);
$lowStockStmt->execute();
$lowStockCount = (int) $lowStockStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root { --teal-primary: #00796B; --teal-dark: #004D40; --teal-light: rgba(0, 121, 107, 0.08); }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .main-content { padding: 40px 0; }
        .inventory-container { max-width: 1500px; margin: 0 auto; }
        .asset-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .badge-low { background-color: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2; }
        .text-teal { color: var(--teal-primary) !important; }
        .bg-teal { background-color: var(--teal-primary) !important; }
        .bg-teal-light { background-color: var(--teal-light) !important; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .table-hover tbody tr:hover { background-color: #f0f7f6; transition: 0.2s; }
        .btn-teal { background-color: var(--teal-primary); color: white; border: none; }
        .btn-teal:hover { background-color: var(--teal-dark); color: white; }

        .inventory-container { padding: 0 1rem; }
        .asset-name-cell { word-break: break-word; }
        .asset-tag-btn { display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 0.4rem; padding: 0.2rem 0.35rem; }
        .action-buttons { display: flex; justify-content: flex-end; gap: 0.35rem; flex-wrap: wrap; }

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

        /* Stylings to automatically target and beautify the AJAX content of #tagListBody */
        #tagListBody table {
            margin-bottom: 0;
            width: 100%;
        }
        #tagListBody th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5c636a;
            font-weight: 700;
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            padding: 12px 24px;
        }
        #tagListBody td {
            padding: 14px 24px;
            font-size: 0.875rem;
            color: #212529;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        #tagListBody tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .asset-thumb { width: 40px; height: 40px; }
            .asset-tag-btn { width: 100%; justify-content: flex-start; }
            .action-buttons { justify-content: flex-end; }
            .table-responsive { overflow-x: auto; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container-fluid inventory-container">

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('msg')) {
                const msg = urlParams.get('msg');
                if (msg === 'deleted') {
                    Swal.fire({ title: 'Deleted!', text: 'The asset has been removed successfully.', icon: 'success', showConfirmButton: false, timer: 4000 });
                }
                if (msg === 'success') {
                    Swal.fire({ title: 'Success!', text: 'Asset details have been updated.', icon: 'success', showConfirmButton: false, timer: 2000 });
                }
                urlParams.delete('msg');
                const newQuery = urlParams.toString() ? '?' + urlParams.toString() : '';
                window.history.replaceState({}, document.title, window.location.pathname + newQuery);
            }
        });
        </script>

        <div class="d-flex align-items-center justify-content-between mb-2 gap-3">
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
            <a href="add_assets.php" class="btn btn-teal rounded-pill px-4 py-2 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Add New Asset
            </a>
        </div>
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Inventory Management</h2>
            <p class="text-muted mb-0">Overview of ITQSHHB assets and equipment stock.</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card rounded-4 shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Total Assets</h5>
                                <p class="text-muted mb-0">All inventory records</p>
                            </div>
                            <div class="badge bg-teal rounded-pill py-2 px-3 fs-6"><?php echo $totalAssets; ?></div>
                        </div>
                        <div class="progress rounded-pill" style="height: 12px; background-color: #e9f3f1;">
                            <div class="progress-bar bg-teal rounded-pill" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card rounded-4 shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Low Stock Items</h5>
                                <p class="text-muted mb-0">At or below <?php echo $low_stock_threshold; ?> units (Global)</p>
                            </div>
                            <div class="badge bg-warning rounded-pill py-2 px-3 fs-6"><?php echo $lowStockCount; ?></div>
                        </div>
                        <div class="progress rounded-pill" style="height: 12px; background-color: #fff4e5;">
                            <div class="progress-bar bg-warning rounded-pill" role="progressbar" style="width: <?php echo $totalAssets > 0 ? ($lowStockCount / $totalAssets) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card rounded-4 mb-4">
            <div class="card-body">
                <form method="GET" id="filterForm" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="adminSearch" class="form-control border-start-0 ps-0" 
                                   placeholder="Search by name, category or ID..." value="<?php echo htmlspecialchars($search); ?>">     
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="category" id="categoryFilter" onchange="document.getElementById('filterForm').submit();">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="d-none"></button>
                </form>
            </div>
        </div>

        <div class="card rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Image</th>
                            <th>Asset Name</th>
                            <th class="d-none d-md-table-cell">Category</th>
                            <th class="d-none d-lg-table-cell">Stock (Current / Total)</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assets) > 0): ?>
                            <?php foreach ($assets as $row): ?>
                                <?php 
                                    $current = $row['current_stock'];
                                    $total = $row['total_stock'];
                                    $is_consumable = ($row['category'] === 'Consumables' || $row['category'] === 'Stationery');
                                    
                                    if ($current <= 0) {
                                        $status_label = $is_consumable ? "Out of Stock" : "Not Available";
                                        $badge_class = "bg-danger text-white";
                                    } elseif ($current <= $low_stock_threshold) {
                                        $status_label = "Low Stock";
                                        $badge_class = "badge-low";
                                    } else {
                                        $status_label = "Available";
                                        $badge_class = "bg-success text-white";
                                    }

                                    $img = !empty($row['asset_image']) ? "../assets/upload/" . $row['asset_image'] : "../assets/img/no_image_available.jpg";
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <img src="<?php echo $img; ?>" class="asset-thumb border" alt="Asset">
                                    </td>
                                    <td class="asset-name-cell">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['asset_name']); ?></div>
                                        <small class="text-muted">ID: #<?php echo str_pad($row['asset_id'], 4, '0', STR_PAD_LEFT); ?></small>
                                        <button class="btn btn-link btn-sm p-0 text-decoration-none fw-bold asset-tag-btn view-tags-trigger" 
                                                data-asset-id="<?php echo $row['asset_id']; ?>"
                                                data-asset-name="<?php echo htmlspecialchars($row['asset_name'], ENT_QUOTES); ?>"
                                                style="color: var(--teal-primary); font-size: 0.75rem;">
                                            <i class="bi bi-tag-fill"></i> View Asset Tags
                                        </button>
                                    </td>
                                    <td class="d-none d-md-table-cell"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                    <td class="d-none d-lg-table-cell">
                                        <span class="fw-bold text-dark"><?php echo $current; ?></span> 
                                        <span class="text-muted">/ <?php echo $total; ?></span>
                                        <div class="progress mt-1" style="height: 5px; width: 80px;">
                                            <?php 
                                                $percent = ($total > 0) ? ($current / $total) * 100 : 0;
                                                $bar_color = ($percent <= 20) ? 'bg-danger' : 'bg-teal';
                                            ?>
                                            <div class="progress-bar <?php echo $bar_color; ?>" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                    </td>
                                    <td><span class="badge <?php echo $badge_class; ?> rounded-pill px-3"><?php echo $status_label; ?></span></td>
                                    <td class="text-end pe-4">
                                        <div class="action-buttons">
                                            <a href="edit_asset.php?id=<?php echo $row['asset_id']; ?>" class="btn btn-light btn-sm border">
                                                <i class="bi bi-pencil text-dark"></i>
                                            </a>
                                            <button class="btn btn-light btn-sm border text-danger" onclick="showDeleteModal(<?php echo $row['asset_id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-search display-6 d-block mb-3 text-secondary"></i>
                                    No assets found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-12 d-flex flex-column flex-sm-row align-items-center justify-content-between">
                <div class="text-muted small mb-3 mb-sm-0">
                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + count($assets), $totalAssets); ?> of <?php echo $totalAssets; ?> items
                </div>
                <nav aria-label="Asset pagination">
                    <ul class="pagination mb-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" tabindex="-1">Previous</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <p class="text-muted">Are you sure you want to delete this asset? This action will permanently remove the record.</p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger rounded-pill px-4 text-white">Delete Permanently</a>
            </div>
        </div>
    </div>
</div>

<!-- Beautiful, Clean Redesigned Asset Tag Modal -->
<div class="modal fade" id="assetTagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Changed to lg for more breathability -->
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3 bg-white rounded-top-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-teal-light p-2 rounded-3 text-teal d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-tags-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 fs-5" id="tagModalTitle">Asset Serials</h5>
                        <p class="text-muted mb-0 small" style="font-size: 0.75rem;">Manage individual physical tag and status records.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Clean Form Panel with Consistent Heights -->
                <div class="p-4 bg-light border-bottom">
                    <small class="fw-bold text-secondary text-uppercase d-block mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Add New Tag / Serial</small>
                    <form id="addTagForm" class="row g-2 align-items-center">
                        <input type="hidden" name="asset_id" id="add_tag_asset_id">
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-hash"></i></span>
                                <input type="text" name="unique_tag" class="form-control border-start-0" placeholder="e.g. ITQSHHB-001L" style="height: 42px;">
                            </div>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" class="btn btn-outline-secondary h-100 d-flex align-items-center justify-content-center gap-2" 
                                    onclick="generateTag(document.getElementById('add_tag_asset_id').value)" 
                                    style="height: 42px; white-space: nowrap;">
                                <i class="bi bi-magic"></i> Auto-Generate
                            </button>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-teal h-100 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="height: 42px;">
                                <i class="bi bi-plus-circle-fill"></i> Add Tag
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Automatically beautified AJAX injected content wrapper -->
                <div id="tagListBody" class="bg-white">
                    <div class="p-5 text-center"><div class="spinner-border text-teal"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showDeleteModal(id) {
    document.getElementById('confirmDeleteBtn').href = 'delete_asset.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Modern event delegation for reading HTML5 data attributes securely
document.querySelectorAll('.view-tags-trigger').forEach(button => {
    button.addEventListener('click', function() {
        const assetId = this.getAttribute('data-asset-id');
        const assetName = this.getAttribute('data-asset-name');
        
        document.getElementById('tagModalTitle').innerText = "Serials: " + assetName;
        document.getElementById('add_tag_asset_id').value = assetId;
        loadTags(assetId);
        new bootstrap.Modal(document.getElementById('assetTagModal')).show();
    });
});

function loadTags(assetId) {
    fetch(`actions/get_asset_tags.php?asset_id=${assetId}`)
        .then(res => res.text())
        .then(data => { document.getElementById('tagListBody').innerHTML = data; });
}

// Add Tag AJAX
document.getElementById('addTagForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const assetId = formData.get('asset_id');

    fetch('actions/add_asset_tag.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Tag added successfully', showConfirmButton: false, timer: 2000, timerProgressBar: true });
            this.reset();
            loadTags(assetId);
        } else {
            Swal.fire({ title: 'Cannot Add Tag', text: data.message, icon: 'error', confirmButtonColor: '#00796B' });
        }
    })
    .catch(() => {
        Swal.fire({ title: 'Error', text: 'Unable to reach the server.', icon: 'error', confirmButtonColor: '#00796B' });
    });
});

function generateTag(assetId) {
    if (!assetId) {
        Swal.fire({ title: 'Missing Asset', text: 'Please open the tag modal from an asset row first.', icon: 'warning', confirmButtonColor: '#00796B' });
        return;
    }

    const formData = new FormData();
    formData.append('asset_id', assetId);
    formData.append('auto_generate', '1');

    fetch('actions/add_asset_tag.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Tag generated: ' + data.unique_tag, showConfirmButton: false, timer: 2200, timerProgressBar: true });
            loadTags(assetId);
        } else {
            Swal.fire({ title: 'Cannot Generate Tag', text: data.message, icon: 'error', confirmButtonColor: '#00796B' });
        }
    })
    .catch(() => {
        Swal.fire({ title: 'Error', text: 'Unable to reach the server.', icon: 'error', confirmButtonColor: '#00796B' });
    });
}

// Delete Tag AJAX
function deleteTag(tagId, assetId) {
    Swal.fire({
        title: 'Remove Tag?',
        text: "This serial number will be permanently deleted.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00796B',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('tag_id', tagId);
            fetch('actions/delete_asset_tag.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Tag removed', showConfirmButton: false, timer: 2000 });
                    loadTags(assetId);
                } else { Swal.fire('Error!', data.message, 'error'); }
            });
        }
    });
}

// Update Tag Status AJAX (Upgrade C)
function updateTagStatus(tagId, assetId, status, uniqueTag) {
    const formData = new FormData();
    formData.append('tag_id', tagId);
    formData.append('asset_id', assetId);
    formData.append('status', status);
    formData.append('unique_tag', uniqueTag);

    fetch('actions/update_tag_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ 
                toast: true, 
                position: 'top-end', 
                icon: 'success', 
                title: `${uniqueTag} updated to ${status}`, 
                showConfirmButton: false, 
                timer: 2000,
                timerProgressBar: true
            });
            loadTags(assetId); // Dynamically reload the tags inside the modal
        } else {
            Swal.fire('Error', data.message, 'error');
            loadTags(assetId); // Reload tags to revert selected dropdown state on error
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Could not reach the server.', 'error');
        loadTags(assetId);
    });
}
</script>

</body>
</html>