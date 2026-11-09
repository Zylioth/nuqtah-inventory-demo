<?php
session_start();
include '../../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized");
}

$asset_id = $_GET['asset_id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM asset_tags WHERE asset_id = ? ORDER BY unique_tag ASC");
$stmt->execute([$asset_id]);
$tags = $stmt->fetchAll();

if (empty($tags)) {
    echo '<div class="p-5 text-center text-muted">
            <i class="bi bi-tag display-6 d-block mb-2 text-secondary"></i>
            No individual serial tags registered for this asset.
          </div>';
} else {
    echo '<table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Asset Tag / Serial</th>
                    <th>Current Condition</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>';
    foreach ($tags as $t) {
        $status = $t['status'];
        
        // Custom styling variables to transform selects into dynamic color-coded badges
        $badge_style = "";
        if ($status == 'Available') {
            $badge_style = "background-color: #E8F5E9; color: #1B5E20; border: 1px solid #C8E6C9;";
        } elseif ($status == 'On Loan') {
            $badge_style = "background-color: #E3F2FD; color: #0D47A1; border: 1px solid #BBDEFB;";
        } elseif ($status == 'Maintenance') {
            $badge_style = "background-color: #FFF3E0; color: #E65100; border: 1px solid #FFE0B2;";
        } elseif ($status == 'Damaged') {
            $badge_style = "background-color: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2;";
        }

        echo "<tr>
                <td class='fw-bold text-dark ps-4'>
                    <code class='bg-light px-2 py-1 rounded text-dark' style='font-size: 0.9rem;'>{$t['unique_tag']}</code>
                </td>
                <td>";
        
        // If the item is active on a student loan, keep it disabled to lock down integrity
        if ($status === 'On Loan') {
            echo "<span class='badge rounded-pill px-3 py-2 fw-bold' style='{$badge_style}'>
                    <i class='bi bi-person-fill me-1'></i>On Loan
                  </span>";
        } else {
            // Render an elegant inline dropdown status editor (Upgrade C)
            echo "<select class='form-select form-select-sm rounded-pill fw-bold text-center px-3' 
                          style='{$badge_style} width: 160px; height: 32px; -webkit-appearance: none; -moz-appearance: none; appearance: none; cursor: pointer;'
                          onchange='updateTagStatus({$t['tag_id']}, {$t['asset_id']}, this.value, \"{$t['unique_tag']}\")'>
                    <option value='Available' " . ($status == 'Available' ? 'selected' : '') . ">✅ Available</option>
                    <option value='Maintenance' " . ($status == 'Maintenance' ? 'selected' : '') . ">🛠️ Maintenance</option>
                    <option value='Damaged' " . ($status == 'Damaged' ? 'selected' : '') . ">❌ Damaged</option>
                  </select>";
        }

        echo "</td>
                <td class='text-end pe-4'>";
        
        // Do not allow deleting tags if they are currently on loan
        if ($status === 'On Loan') {
            echo "<button class='btn btn-sm btn-outline-secondary border-0 opacity-50' disabled title='Cannot delete active loans'>
                    <i class='bi bi-trash3'></i>
                  </button>";
        } else {
            echo "<button class='btn btn-sm btn-outline-danger border-0' 
                            onclick='deleteTag({$t['tag_id']}, {$t['asset_id']})'
                            title='Delete Serial'>
                        <i class='bi bi-trash3'></i>
                    </button>";
        }

        echo "  </td>
            </tr>";
    }
    echo '</tbody></table>';
}