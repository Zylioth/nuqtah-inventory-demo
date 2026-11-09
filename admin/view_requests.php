<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch all borrowing requests with detailed joins
$query = "SELECT r.*, u.full_name, a.asset_name, a.category, t.unique_tag as assigned_tag_name 
          FROM borrowing_requests r
          JOIN users u ON r.user_id = u.user_id
          JOIN assets a ON r.asset_id = a.asset_id
          LEFT JOIN asset_tags t ON r.tag_id = t.tag_id
          ORDER BY r.request_date DESC";
$stmt = $pdo->query($query);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing Requests - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root { --teal-primary: #00796B; --teal-dark: #004D40; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        
        /* Full Page Layout */
        .main-content { padding: 40px 0; }
        .requests-container { max-width: 1400px; margin: 0 auto; }

        .bg-teal { background-color: var(--teal-primary) !important; }
        .bg-purple { background-color: #6f42c1 !important; }
        .text-teal { color: var(--teal-primary) !important; }
        
        .status-badge { font-size: 0.8rem; padding: 6px 14px; font-weight: 600; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .table-hover tbody tr:hover { background-color: #f0f7f6; transition: 0.2s; }

        /* Custom styling for the timeline schedule labels */
        .schedule-item { display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.35rem; }
        .schedule-item:last-child { margin-bottom: 0; }
        .schedule-icon { font-size: 0.95rem; margin-top: 1px; }
        .schedule-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #8c959d; }
        .schedule-val { font-size: 13px; color: #333333; line-height: 1.2; }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        .animate-pulse {
            animation: pulse-red 2s infinite;
        }

        /* Modernized modal styling to match the tags modal */
        .modal-content-custom {
            border: none;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.175);
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

        /* Flexible actions column layout */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container-fluid requests-container">

        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-3">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
                <h2 class="fw-bold mb-0">Borrowing Requests</h2>
            </div>
            <div class="ms-md-5 ps-md-2">
                <p class="text-muted mb-0">Review and manage equipment loan applications for ITQSHHB.</p>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-dismissible fade show border-0 shadow-sm rounded-3 ms-md-5 mb-4 
                <?php 
                    if ($_GET['msg'] == 'issued' || $_GET['msg'] == 'approved') echo 'alert-success';
                    elseif ($_GET['msg'] == 'returned') echo 'alert-info';
                    elseif ($_GET['msg'] == 'rejected') echo 'alert-warning';
                    elseif ($_GET['msg'] == 'error') echo 'alert-danger';
                    else echo 'alert-primary';
                ?>" role="alert">
                
                <i class="bi <?php 
                    if ($_GET['msg'] == 'issued' || $_GET['msg'] == 'approved') echo 'bi-check-circle-fill';
                    elseif ($_GET['msg'] == 'returned') echo 'bi-arrow-left-right';
                    elseif ($_GET['msg'] == 'rejected') echo 'bi-x-circle-fill';
                    else echo 'bi-exclamation-triangle-fill';
                ?> me-2"></i>

                <strong>
                    <?php 
                        if ($_GET['msg'] == 'approved') echo 'Request has been Approved!';
                        elseif ($_GET['msg'] == 'issued') echo 'Item Issued Successfully!';
                        elseif ($_GET['msg'] == 'returned') echo 'Item Returned & Restocked!';
                        elseif ($_GET['msg'] == 'rejected') echo 'Request has been Rejected.';
                        elseif ($_GET['msg'] == 'error') echo 'Something went wrong. Please try again.';
                        else echo 'Action completed.';
                    ?>
                </strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-5">
                <div class="input-group shadow-sm ms-md-5">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="requestSearch" class="form-control border-start-0 ps-0" 
                        placeholder="Search by requester, asset, or status..." onkeyup="filterRequests()">
                </div>
            </div>
        </div>

        <div class="card rounded-4 overflow-hidden ms-md-5">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Requester</th>
                            <th>Asset Item</th>
                            <th>Asset Tag</th> 
                            <th>Qty</th> 
                            <th>Timeline Schedule</th> 
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $row): ?>
                            <?php 
                                $today = date('Y-m-d');
                                $isOverdue = ($row['status'] == 'On Loan' && $today > $row['return_date']);
                                $status = $row['status'];
                                $badge_class = "bg-secondary text-white";
                                
                                if ($isOverdue) {
                                    $badge_class = "bg-danger text-white animate-pulse";
                                    $display_status = "OVERDUE";
                                } else {
                                    $display_status = $status;
                                    if ($status == 'Pending') $badge_class = "bg-warning text-dark";
                                    elseif ($status == 'Approved') $badge_class = "bg-primary text-white";
                                    elseif ($status == 'On Loan') $badge_class = "bg-success text-white";
                                    elseif ($status == 'Issued') $badge_class = "bg-purple text-white"; 
                                    elseif ($status == 'Returned') $badge_class = "bg-info text-dark";
                                    elseif ($status == 'Rejected') $badge_class = "bg-danger text-white";
                                }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <small class="text-muted">ID: #<?php echo htmlspecialchars($row['user_id']); ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['asset_name']); ?></div>
                                    <span class="badge bg-light text-dark border mt-1"><?php echo htmlspecialchars($row['category']); ?></span>
                                </td>
                                
                                <td>
                                    <?php if (!empty($row['assigned_tag_name'])): ?>
                                        <span class="badge bg-teal text-white shadow-sm" style="font-family: monospace;">
                                            <i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($row['assigned_tag_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not Assigned</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge rounded-pill" style="background-color: rgba(0, 121, 107, 0.1); color: #004D40; padding: 6px 12px;">
                                        <?php echo htmlspecialchars($row['quantity']); ?>
                                    </span>
                                </td>

                                <td style="min-width: 250px;">
                                    <div class="d-flex flex-column py-1">
                                        <!-- Requested date row -->
                                        <div class="schedule-item">
                                            <i class="bi bi-file-earmark-plus text-muted schedule-icon"></i>
                                            <div>
                                                <span class="schedule-label">Requested</span>
                                                <span class="schedule-val d-block"><?php echo date('d M Y', strtotime($row['request_date'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Pickup date row -->
                                        <div class="schedule-item">
                                            <i class="bi bi-calendar2-event text-primary schedule-icon"></i>
                                            <div>
                                                <span class="schedule-label">Pickup On</span>
                                                <span class="schedule-val d-block">
                                                    <?php if (!empty($row['pickup_date'])): ?>
                                                        <span class="text-primary fw-semibold"><?php echo date('d M Y', strtotime($row['pickup_date'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Same-day / Not specified</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Handover/Issued date row -->
                                        <div class="schedule-item">
                                            <i class="bi bi-box-arrow-right text-success schedule-icon"></i>
                                            <div>
                                                <span class="schedule-label">Issued On</span>
                                                <span class="schedule-val d-block">
                                                    <?php if (!empty($row['issued_date'])): ?>
                                                        <span class="text-success fw-semibold"><?php echo date('d M Y, h:i A', strtotime($row['issued_date'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Waiting for handover</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Return due date row -->
                                        <div class="schedule-item">
                                            <i class="bi bi-calendar-check <?php echo $isOverdue ? 'text-danger' : 'text-muted'; ?> schedule-icon"></i>
                                            <div>
                                                <span class="schedule-label">Return Due</span>
                                                <span class="schedule-val d-block <?php echo $isOverdue ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                                    <?php echo !empty($row['return_date']) ? date('d M Y', strtotime($row['return_date'])) : 'N/A'; ?>
                                                    <?php if ($isOverdue): ?>
                                                        <i class="bi bi-exclamation-triangle-fill ms-1"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Actual return date row -->
                                        <?php if (!empty($row['actual_return_date'])): ?>
                                            <?php 
                                                $deadline_date = date('Y-m-d', strtotime($row['return_date']));
                                                $actual_date = date('Y-m-d', strtotime($row['actual_return_date']));
                                                $isLate = ($actual_date > $deadline_date);
                                                $statusColor = $isLate ? 'text-danger' : 'text-success';
                                            ?>
                                            <div class="schedule-item">
                                                <i class="bi <?php echo $isLate ? 'bi-exclamation-circle text-danger' : 'bi-check-circle text-success'; ?> schedule-icon"></i>
                                                <div>
                                                    <span class="schedule-label <?php echo $statusColor; ?>">Actually Returned</span>
                                                    <span class="schedule-val d-block <?php echo $statusColor; ?> fw-bold">
                                                        <?php echo date('d M Y', strtotime($row['actual_return_date'])); ?>
                                                        <?php if ($isLate): ?>
                                                            <small class="d-block" style="font-size: 0.65rem;">(LATE RETURN)</small>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge <?php echo $badge_class; ?> rounded-pill status-badge">
                                        <?php echo htmlspecialchars($display_status); ?>
                                    </span>
                                </td>

                                <td class="text-end pe-4">
                                    <!-- Inner Action wrapper block to avoid HTML5 table engine flex stretching bugs -->
                                    <div class="action-buttons">
                                        <?php if ($status == 'Pending'): ?>
                                            <a href="actions/process_request.php?id=<?php echo $row['request_id']; ?>&action=approve" class="btn btn-sm btn-success rounded-pill px-3">Approve</a>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3 reject-trigger" 
                                                    data-request-id="<?php echo $row['request_id']; ?>">
                                                Reject
                                            </button>
                                        
                                        <?php elseif ($status == 'Approved'): ?>
                                            <button class="btn btn-sm btn-primary rounded-pill px-3 handover-trigger" 
                                                    data-request-id="<?php echo $row['request_id']; ?>" 
                                                    data-asset-id="<?php echo $row['asset_id']; ?>">
                                                <i class="bi bi-hand-index-thumb me-1"></i>Issue Item
                                            </button>

                                        <?php elseif ($status == 'On Loan'): ?>
                                            <button class="btn <?php echo $isOverdue ? 'btn-danger' : 'btn-dark'; ?> btn-sm rounded-pill px-3 shadow-sm return-trigger" 
                                                    data-request-id="<?php echo $row['request_id']; ?>">
                                                <i class="bi bi-arrow-left-right me-1"></i>Mark Returned
                                            </button>
                                            
                                        <?php elseif ($status == 'Issued'): ?>
                                            <span class="text-muted small"><i class="bi bi-check2-all me-1"></i>Finalized</span>
                                        
                                        <?php else: ?>
                                            <span class="text-muted small italic"><?php echo htmlspecialchars($status); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upgraded Handover Modal (Matches view_asset_tags modal) -->
<div class="modal fade" id="handoverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-badge text-teal" style="background-color: rgba(0, 121, 107, 0.1);">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Pre-Loan Inspection</h5>
                        <small class="text-muted">Verify asset condition before handover</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/process_handover.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <input type="hidden" name="request_id" id="modal_request_id">
                    
                    <div class="mb-4">
                        <div class="form-check p-3 rounded-3 border bg-light mb-2">
                            <input class="form-check-input ms-0 me-3" type="checkbox" id="check1" required>
                            <label class="form-check-label text-dark fw-semibold" for="check1">Item is physically intact</label>
                        </div>
                        <div class="form-check p-3 rounded-3 border bg-light mb-2">
                            <input class="form-check-input ms-0 me-3" type="checkbox" id="check2" required>
                            <label class="form-check-label text-dark fw-semibold" for="check2">All peripherals included</label>
                        </div>
                        <div class="form-check p-3 rounded-3 border bg-light mb-0">
                            <input class="form-check-input ms-0 me-3" type="checkbox" id="check3" required>
                            <label class="form-check-label text-dark fw-semibold" for="check3">Device powers on correctly</label>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label small fw-bold text-dark">Assign Specific Asset Tag / Serial</label>
                        <select class="form-select border-success shadow-sm mt-1" name="assigned_tag" id="tag_dropdown" required>
                            <option value="" disabled selected>Loading available items...</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-dark">Admin Handover Notes (Optional)</label>
                        <textarea class="form-control rounded-3" name="handover_note" rows="2" placeholder="Describe any minor cosmetic marks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 text-white">Confirm & Issue Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upgraded Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-badge text-dark" style="background-color: rgba(33, 37, 41, 0.1);">
                        <i class="bi bi-box-arrow-in-left"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Post-Loan Inspection</h5>
                        <small class="text-muted">Process the return of equipment</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/process_return.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <input type="hidden" name="request_id" id="return_request_id">
                    
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <label class="form-label small fw-bold text-dark">Asset Return Status</label>
                        <select class="form-select border-dark shadow-sm mt-1" name="return_status" required>
                            <option value="Available" selected>✅ Good Condition (Back to Stock)</option>
                            <option value="Maintenance">🛠️ Under Maintenance (Needs Repair)</option>
                            <option value="Damaged">❌ Damaged / Broken (Remove from Stock)</option>
                        </select>
                    </div>

                    <div class="form-check p-3 rounded-3 border bg-light mb-3">
                        <input class="form-check-input ms-0 me-3" type="checkbox" id="confirmInspect" required>
                        <label class="form-check-label text-dark fw-semibold" for="confirmInspect">I have verified the physical condition</label>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-dark">Return Notes / Observations</label>
                        <textarea class="form-control rounded-3" name="return_note" rows="2" placeholder="e.g. Scratches on lid, missing strap..."></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upgraded Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-badge text-danger" style="background-color: rgba(220, 53, 69, 0.1);">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Reject Borrowing Request</h5>
                        <small class="text-muted">Provide a reason for turning down this request</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/process_request.php" method="POST">
                <div class="modal-body modal-body-custom">
                    <input type="hidden" name="id" id="reject_request_id">
                    <input type="hidden" name="action" value="reject">
                    
                    <div>
                        <label for="reject_note" class="form-label small fw-bold text-dark">Reason for Rejection</label>
                        <textarea class="form-control rounded-3" name="note" id="reject_note" rows="3" required 
                                  placeholder="e.g. This device is scheduled for maintenance during your requested duration."></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Event listeners for modern HTML5 data elements instead of inline PHP-in-JS triggers
document.querySelectorAll('.reject-trigger').forEach(button => {
    button.addEventListener('click', function() {
        const requestId = this.getAttribute('data-request-id');
        document.getElementById('reject_request_id').value = requestId;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });
});

document.querySelectorAll('.handover-trigger').forEach(button => {
    button.addEventListener('click', function() {
        const requestId = this.getAttribute('data-request-id');
        const assetId = this.getAttribute('data-asset-id');
        
        document.getElementById('modal_request_id').value = requestId;
        const dropdown = document.getElementById('tag_dropdown');
        dropdown.innerHTML = '<option disabled>Fetching available tags...</option>';
        new bootstrap.Modal(document.getElementById('handoverModal')).show();

        fetch(`actions/get_available_tags.php?asset_id=${assetId}`)
            .then(response => response.text())
            .then(data => { dropdown.innerHTML = data; })
            .catch(err => { dropdown.innerHTML = '<option disabled>Error loading tags</option>'; });
    });
});

document.querySelectorAll('.return-trigger').forEach(button => {
    button.addEventListener('click', function() {
        const requestId = this.getAttribute('data-request-id');
        document.getElementById('return_request_id').value = requestId;
        new bootstrap.Modal(document.getElementById('returnModal')).show();
    });
});

// Client side list filtering
function filterRequests() {
    const filter = document.getElementById("requestSearch").value.toLowerCase();
    const rows = document.querySelectorAll("table tbody tr");
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
    });
}
</script>

</body>
</html>