<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Ensure CSRF token for admin forms
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Fetch active search and page queries to enable server-side processing
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$itemsPerPage = 15;

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(full_name LIKE :search1 OR username LIKE :search2 OR email LIKE :search3 OR role LIKE :search4)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
    $params[':search4'] = '%' . $search . '%';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count filtered records for pagination calculations
$countQuery = "SELECT COUNT(*) FROM users $where_sql";
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$totalUsers = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalUsers / $itemsPerPage));

if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $itemsPerPage;

// Fetch filtered and paginated slice of users
$query = "SELECT user_id, full_name, username, email, role, account_status, created_at 
          FROM users $where_sql 
          ORDER BY created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { 
            --teal-primary: #00796B; 
            --teal-dark: #004D40; 
            --teal-light: rgba(0, 121, 107, 0.08); 
        }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        
        .main-content { 
            padding: 40px 15px; 
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Premium UI Accents */
        .bg-teal-light { background-color: var(--teal-light) !important; }
        .text-teal { color: var(--teal-primary) !important; }
        .btn-teal { background-color: var(--teal-primary); color: white; border: none; transition: 0.2s; }
        .btn-teal:hover { background-color: var(--teal-dark); color: white; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05); }
        .table-hover tbody tr:hover { background-color: #f0f7f6; transition: 0.2s; }

        /* Modernized Badges */
        .role-badge { font-size: 0.75rem; padding: 5px 12px; font-weight: 600; }
        .badge-admin { background-color: rgba(0, 121, 107, 0.1); color: var(--teal-primary); border: 1px solid rgba(0, 121, 107, 0.15); }
        .badge-staff { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; border: 1px solid rgba(13, 110, 253, 0.15); }
        .badge-student { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.15); }

        .status-badge { font-size: 0.75rem; padding: 5px 12px; font-weight: 600; }
        .status-active { background-color: rgba(25, 135, 84, 0.1); color: #198754; border: 1px solid rgba(25, 135, 84, 0.15); }
        .status-suspended { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.15); }
        .status-pending { background-color: rgba(255, 193, 7, 0.1); color: #b58100; border: 1px solid rgba(255, 193, 7, 0.15); }

        /* Pagination Alignment styling */
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

        /* Premium Modal Overrides */
        .modal-content-custom {
            border: none;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.12);
            border-radius: 16px;
        }
        .modal-header-custom {
            border-bottom: 1px solid #f0f0f0;
            padding: 1.5rem;
        }
        .modal-body-custom {
            padding: 1.5rem;
        }
        .modal-footer-custom {
            border-top: 1px solid #f0f0f0;
            padding: 1.25rem 1.5rem;
        }
        .modal-icon-badge {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        @media (max-width: 768px) {
            .header-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            .btn-add-user {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 header-flex">
        <div>
            <div class="d-flex align-items-center mb-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-3">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
                <h2 class="fw-bold mb-0 text-dark">User Management</h2>
            </div>
            <p class="text-muted mb-0 ms-md-5 ps-md-2">Control access levels and manage user accounts for Nuqtah.</p>
        </div>
        <button class="btn btn-teal text-white rounded-pill px-4 py-2 shadow-sm btn-add-user" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-2"></i> Add New User
        </button>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php 
            $success_msgs = ['success', 'activated', 'updated', 'deleted'];
            $is_success = in_array($_GET['msg'], $success_msgs);
            $alert_class = $is_success ? 'alert-success' : 'alert-danger';
            $icon_class = $is_success ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        ?>
        <div class="alert alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 <?php echo $alert_class; ?>" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi <?php echo $icon_class; ?> fs-5 me-3"></i>
                <div>
                    <?php 
                        if($_GET['msg'] == 'activated') echo "<strong>Account Activated!</strong> The user has been notified via email.";
                        elseif ($_GET['msg'] == 'updated') echo "<strong>Success!</strong> User information has been updated.";
                        elseif ($_GET['msg'] == 'success') echo "Action completed successfully!";
                        elseif ($_GET['msg'] == 'exists') echo "<strong>Registration Error!</strong> That Username or ID already exists.";
                        elseif ($_GET['msg'] == 'self_demote_error') echo "<strong>Security Alert!</strong> You cannot change your own Admin status.";
                        elseif ($_GET['msg'] == 'self_delete_error') echo "<strong>Action Denied!</strong> You cannot delete your own account.";
                        elseif ($_GET['msg'] == 'deleted') echo "<strong>Success!</strong> User has been removed from the system.";
                        else echo "<strong>Error!</strong> Something went wrong. Please check your data.";
                    ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" id="searchForm">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="userSearch" class="form-control border-start-0 ps-0" 
                        placeholder="Search by name, username, email or role..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3">Full Name</th>
                        <th>Username / ID</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </td>
                                <td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                                <td>
                                    <?php if ($user['role'] == 'Admin'): ?>
                                        <span class="badge rounded-pill role-badge badge-admin">Admin</span>
                                    <?php elseif ($user['role'] == 'Staff'): ?>
                                        <span class="badge rounded-pill role-badge badge-staff">Staff</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill role-badge badge-student">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $status = $user['account_status'];
                                        $badge_class = 'status-active';
                                        if ($status == 'Suspended') $badge_class = 'status-suspended';
                                        if ($status == 'Pending') $badge_class = 'status-pending';
                                    ?>
                                    <span class="badge rounded-pill status-badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td class="text-secondary"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <!-- Replaced JSON escaping attribute string inside onclick with HTML5 attributes -->
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 edit-user-trigger" 
                                            data-user-id="<?php echo $user['user_id']; ?>"
                                            data-full-name="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>"
                                            data-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                            data-role="<?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?>"
                                            data-status="<?php echo htmlspecialchars($user['account_status'], ENT_QUOTES); ?>">
                                            Edit Profile
                                        </button>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-link text-danger p-2 delete-user-trigger" 
                                                    title="Delete User" 
                                                    data-user-id="<?php echo $user['user_id']; ?>"
                                                    data-full-name="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x display-6 d-block mb-3 text-secondary"></i>
                                No users found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Controller -->
    <?php if ($totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-12 d-flex flex-column flex-sm-row align-items-center justify-content-between">
                <div class="text-muted small mb-3 mb-sm-0">
                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + count($users), $totalUsers); ?> of <?php echo $totalUsers; ?> users
                </div>
                <nav aria-label="User navigation">
                    <ul class="pagination mb-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>" tabindex="-1">Previous</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add User Modal (Upgraded Design Structure) -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-badge text-success bg-success bg-opacity-10">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Create New User</h5>
                        <small class="text-muted">Register and configure a new system user</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/add_users.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="modal-body modal-body-custom">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Full Name</label>
                        <input type="text" name="full_name" class="form-control rounded-3" placeholder="Enter full name" required style="height: 42px;">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Username / ID</label>
                            <input type="text" name="username" class="form-control rounded-3" placeholder="B2023..." required style="height: 42px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Role</label>
                            <select class="form-select rounded-3" name="role" style="height: 42px;">
                                <option value="Student">Student</option>
                                <option value="Staff">Staff</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Email Address</label>
                        <input type="email" name="email" class="form-control rounded-3" placeholder="email@example.com" required style="height: 42px;">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark mb-1">Temporary Password</label>
                        <input type="password" name="password" class="form-control rounded-3" required style="height: 42px;">
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal text-white rounded-pill px-4">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal (Upgraded Design Structure) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-badge text-teal bg-teal bg-opacity-10">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Edit User Profile</h5>
                        <small class="text-muted">Modify account details and privilege controls</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/update_user_full.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="modal-body modal-body-custom">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control rounded-3" required style="height: 42px;">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Username / ID</label>
                            <input type="text" name="username" id="edit_username" class="form-control rounded-3" required style="height: 42px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Role</label>
                            <select class="form-select rounded-3" name="role" id="edit_role" style="height: 42px;">
                                <option value="Student">Student</option>
                                <option value="Staff">Staff</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control rounded-3" required style="height: 42px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Account Status</label>
                            <select class="form-select rounded-3 fw-bold" name="account_status" id="edit_account_status" style="height: 42px;">
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal text-white rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Upgraded Design Structure) -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content modal-content-custom text-center">
            <div class="modal-body modal-body-custom py-4">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-trash3-fill fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Remove User Account?</h5>
                <p class="text-muted small mb-1">Are you sure you want to permanently remove:</p>
                <p id="delete_user_name" class="fw-bold text-dark mb-3"></p>
                
                <div class="d-flex flex-column gap-2 mt-2">
                    <a href="#" id="delete_confirm_btn" class="btn btn-danger w-100 rounded-pill py-2 text-white">Delete Account</a>
                    <button type="button" class="btn btn-light w-100 rounded-pill py-2" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Attach event listeners to the triggers using modern data-attribute mapping
document.querySelectorAll('.edit-user-trigger').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_user_id').value = this.getAttribute('data-user-id');
        document.getElementById('edit_full_name').value = this.getAttribute('data-full-name');
        document.getElementById('edit_username').value = this.getAttribute('data-username');
        document.getElementById('edit_email').value = this.getAttribute('data-email');
        document.getElementById('edit_role').value = this.getAttribute('data-role');
        document.getElementById('edit_account_status').value = this.getAttribute('data-status');
        
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    });
});

document.querySelectorAll('.delete-user-trigger').forEach(btn => {
    btn.addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        const fullName = this.getAttribute('data-full-name');
        
        document.getElementById('delete_user_name').innerText = fullName;
        document.getElementById('delete_confirm_btn').setAttribute('data-user-id', userId);
        
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    });
});

// Handle delete button click: perform POST with secure CSRF token validation
document.addEventListener('DOMContentLoaded', function () {
    const delBtn = document.getElementById('delete_confirm_btn');
    if (delBtn) {
        delBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-user-id');
            if (!id) return;

            // Dynamically construct post form parameters
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'actions/delete_user.php';

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = id;
            form.appendChild(inputId);

            const inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden';
            inputCsrf.name = 'csrf_token';
            inputCsrf.value = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
            form.appendChild(inputCsrf);

            document.body.appendChild(form);
            form.submit();
        });
    }
});
</script>
</body>
</html>