<?php
session_start();
include 'includes/db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark notifications as read
$clearNotif = $pdo->prepare("UPDATE borrowing_requests SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$clearNotif->execute([$user_id]);

// Fetch current user requests with joined asset and tag information
$query = "SELECT r.*, a.asset_name, a.category, t.unique_tag 
          FROM borrowing_requests r
          JOIN assets a ON r.asset_id = a.asset_id
          LEFT JOIN asset_tags t ON r.tag_id = t.tag_id
          WHERE r.user_id = ?
          ORDER BY r.request_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$my_requests = $stmt->fetchAll();

$totalRequests = count($my_requests);
$statusCounts = [];
foreach ($my_requests as $requestRow) {
    $statusCounts[$requestRow['status']] = ($statusCounts[$requestRow['status']] ?? 0) + 1;
}
$statusOrder = ['Pending', 'Approved', 'On Loan', 'Issued', 'Returned', 'Rejected'];
$filterStatuses = [];
foreach ($statusOrder as $knownStatus) {
    if (isset($statusCounts[$knownStatus])) {
        $filterStatuses[] = $knownStatus;
    }
}
foreach ($statusCounts as $extraStatus => $count) {
    if (!in_array($extraStatus, $statusOrder, true)) {
        $filterStatuses[] = $extraStatus;
    }
}
$categoryIcons = [
    'Laptops' => 'bi-laptop',
    'Projectors' => 'bi-projector',
    'Accessories' => 'bi-plug',
    'Consumables' => 'bi-box-seam',
    'Others' => 'bi-three-dots',
    'Stationery' => 'bi-pencil',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Nuqtah</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* ---------------------------------------------------------------
           Nuqtah borrowing history - page scoped styles
        --------------------------------------------------------------- */
        :root {
            --brand-teal: #00796B;
            --brand-teal-dark: #005a4d;

            --nq-teal: #00796B;
            --nq-deep: #004D40;
            --nq-paper: #F5F8F7;
            --nq-ink: #0B2B26;
            --nq-body: rgba(0, 77, 64, 0.78);
            --nq-line: rgba(0, 77, 64, 0.12);
            --nq-tint: rgba(0, 121, 107, 0.08);
            --nq-ok: #146C43;
            --nq-low: #9A5B00;
            --nq-out: #B3261E;
            --nq-font: 'Outfit', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }

        body.nq-myreq {
            background-color: var(--nq-paper);
            color: var(--nq-ink);
            font-family: var(--nq-font);
            -webkit-font-smoothing: antialiased;
        }

        .nq-shell {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 1.75rem 1rem 3.5rem;
        }

        /* ---------- Page head ---------- */
        .nq-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem 2rem;
            margin-bottom: 1.5rem;
        }
        .nq-head-copy { max-width: 560px; }
        .nq-eyebrow {
            margin: 0;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--nq-teal);
        }
        .nq-title {
            margin: 0.4rem 0 0.45rem;
            font-size: clamp(1.75rem, 4vw, 2.35rem);
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1.12;
            color: var(--nq-deep);
        }
        .nq-lede { margin: 0; max-width: 48ch; font-size: 0.95rem; color: var(--nq-body); }

        .nq-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.62rem 1rem;
            border: 1px solid transparent;
            border-radius: 11px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            line-height: 1.25;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }
        .nq-btn--primary { background: var(--nq-teal); color: #fff; }
        .nq-btn--primary:hover { background: var(--brand-teal-dark); color: #fff; transform: translateY(-1px); }
        .nq-btn--ghost { border-color: var(--nq-line); background: #fff; color: var(--nq-deep); }
        .nq-btn--ghost:hover {
            border-color: rgba(0, 121, 107, 0.4);
            background: var(--nq-tint);
            color: var(--nq-teal);
            transform: translateY(-1px);
        }

        /* ---------- Status filter ---------- */
        .nq-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-bottom: 1.25rem;
        }
        .nq-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.48rem 0.8rem;
            border: 1px solid var(--nq-line);
            border-radius: 999px;
            background: #fff;
            color: var(--nq-deep);
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.2;
            cursor: pointer;
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }
        .nq-filter-btn:hover { border-color: rgba(0, 121, 107, 0.4); background: var(--nq-tint); color: var(--nq-teal); }
        .nq-filter-btn.is-active { border-color: var(--nq-teal); background: var(--nq-teal); color: #fff; }
        .nq-filter-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 0.3rem;
            border-radius: 999px;
            background: rgba(0, 77, 64, 0.08);
            color: var(--nq-deep);
            font-size: 0.72rem;
            font-weight: 700;
        }
        .nq-filter-btn.is-active .nq-filter-count { background: rgba(255, 255, 255, 0.22); color: #fff; }

        /* ---------- Request cards ---------- */
        .nq-list { display: grid; gap: 0.75rem; }
        .nq-req {
            display: grid;
            gap: 0.6rem 1rem;
            grid-template-columns: minmax(0, 1fr) auto;
            grid-template-areas:
                "main status"
                "facts facts"
                "note note";
            padding: 1rem 1.1rem;
            border: 1px solid var(--nq-line);
            border-radius: 16px;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            animation: nq-rise 0.45s cubic-bezier(0.22, 1, 0.36, 1) backwards;
            animation-delay: calc(var(--d, 0) * 1ms);
        }
        .nq-req[hidden] { display: none; }
        @keyframes nq-rise {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: none; }
        }
        .nq-req-main {
            grid-area: main;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }
        .nq-req-ico {
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 12px;
            background: var(--nq-tint);
            color: var(--nq-teal);
            font-size: 1.05rem;
        }
        .nq-req-name {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 600;
            line-height: 1.3;
            letter-spacing: -0.01em;
            color: var(--nq-deep);
            overflow-wrap: break-word;
        }
        .nq-req-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem 0.5rem;
            margin: 0.15rem 0 0;
            font-size: 0.78rem;
            color: var(--nq-body);
        }
        .nq-req-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.12rem 0.45rem;
            border-radius: 6px;
            background: rgba(0, 121, 107, 0.07);
            color: var(--nq-teal);
            font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace;
            font-size: 0.72rem;
        }
        .nq-sep { opacity: 0.45; }

        .nq-req-facts {
            grid-area: facts;
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 1.5rem;
            margin: 0;
        }
        .nq-req-facts div { display: flex; align-items: baseline; gap: 0.4rem; }
        .nq-req-facts dt {
            margin: 0;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--nq-body);
        }
        .nq-req-facts dd {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--nq-deep);
            font-variant-numeric: tabular-nums;
        }

        .nq-req-status { grid-area: status; justify-self: end; align-self: start; }

        .nq-st {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.32rem 0.68rem;
            border-radius: 999px;
            background: rgba(0, 77, 64, 0.08);
            color: var(--nq-deep);
            font-size: 0.74rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .nq-st::before {
            content: "";
            width: 0.42rem;
            height: 0.42rem;
            border-radius: 50%;
            background: currentColor;
        }
        .nq-st--pending { background: #FFF4D6; color: #7A4E00; }
        .nq-st--approved { background: #E7F0FA; color: #0D47A1; }
        .nq-st--on-loan { background: #E8F5E9; color: #1B5E20; }
        .nq-st--issued { background: #EFE9F8; color: #4A148C; }
        .nq-st--returned { background: #E0F4F7; color: #00565C; }
        .nq-st--rejected { background: #FDECEA; color: #B3261E; }

        .nq-req-note {
            grid-area: note;
            justify-self: start;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            width: fit-content;
            max-width: 100%;
            margin-top: 0.15rem;
            padding: 0.5rem 0.7rem;
            border-radius: 10px;
            background: #F5F8F7;
            font-size: 0.81rem;
            line-height: 1.45;
            color: var(--nq-body);
        }
        .nq-req-note i { flex: 0 0 auto; margin-top: 0.1rem; }
        .nq-req-note strong { color: var(--nq-deep); font-weight: 600; }
        .nq-req-note--ok { background: #F1F9F4; color: #2C5A42; }
        .nq-req-note--ok i { color: var(--nq-ok); }
        .nq-req-note--warn { background: #FFF8E8; color: #6B4A12; }
        .nq-req-note--warn i { color: var(--nq-low); }
        .nq-req-note--danger { background: #FCF3F2; color: #7A2A24; }
        .nq-req-note--danger i { color: var(--nq-out); }
        .nq-req-note--info { background: #F1F6F9; color: #26485E; }
        .nq-req-note--info i { color: #0D476E; }
        .nq-req-note div { min-width: 0; }

        /* ---------- Empty states ---------- */
        .nq-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
            padding: 3rem 1rem;
            border: 1px dashed rgba(0, 77, 64, 0.2);
            border-radius: 18px;
            background: #fff;
            text-align: center;
        }
        .nq-empty-icon {
            display: grid;
            place-items: center;
            width: 3rem;
            height: 3rem;
            margin-bottom: 0.5rem;
            border-radius: 50%;
            background: var(--nq-tint);
            color: var(--nq-teal);
            font-size: 1.35rem;
        }
        .nq-empty h2 { margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--nq-deep); }
        .nq-empty p { margin: 0 0 0.75rem; max-width: 42ch; font-size: 0.9rem; color: var(--nq-body); }
        .nq-empty.is-filtered { margin-top: 0.75rem; }

        /* ---------- Focus ---------- */
        .nq-shell a:focus-visible,
        .nq-shell button:focus-visible {
            outline: 2px solid var(--nq-teal);
            outline-offset: 3px;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 575.98px) {
            .nq-req {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 0.55rem;
                padding: 0.95rem 1rem;
            }
            .nq-req-status { order: 2; align-self: flex-start; }
            .nq-req-facts { order: 3; }
            .nq-req-note { order: 4; }
        }
        @media (min-width: 768px) {
            .nq-shell { padding: 2.5rem 1.5rem 4rem; }
            .nq-head { margin-bottom: 1.75rem; }
            .nq-req {
                grid-template-columns: minmax(0, 1fr) auto auto;
                grid-template-areas:
                    "main facts status"
                    "note note note";
                align-items: center;
                padding: 1.05rem 1.25rem;
            }
            .nq-req-facts { gap: 1.75rem; }
            .nq-req-status { align-self: center; }
        }
        @media (hover: hover) {
            .nq-req:hover { border-color: rgba(0, 121, 107, 0.26); box-shadow: 0 16px 30px -26px rgba(0, 77, 64, 0.6); }
        }
        @media (prefers-reduced-motion: reduce) {
            .nq-req { animation: none; }
            .nq-req, .nq-btn, .nq-filter-btn, .nq-filter-count { transition: none !important; }
        }
    </style>
</head>
<body class="nq-myreq">

<?php include 'includes/header.php'; ?>
<?php include 'includes/users_sidebar.php'; ?>

<main class="nq-shell">

    <header class="nq-head">
        <div class="nq-head-copy">
            <p class="nq-eyebrow">ITQSHHB &middot; ICT Department</p>
            <h1 class="nq-title">My borrowing history</h1>
            <p class="nq-lede">Every request you have made, with its current status and any note from the ICT team.</p>
        </div>
        <a href="inventory_list.php" class="nq-btn nq-btn--ghost">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Back to inventory
        </a>
    </header>

    <?php if ($totalRequests > 0): ?>
        <div class="nq-filters" role="group" aria-label="Filter requests by status">
            <button type="button" class="nq-filter-btn is-active" data-filter="all" aria-pressed="true">
                All
                <span class="nq-filter-count"><?php echo $totalRequests; ?></span>
            </button>
            <?php foreach ($filterStatuses as $filterStatus): ?>
                <button type="button" class="nq-filter-btn" data-filter="<?php echo htmlspecialchars($filterStatus, ENT_QUOTES); ?>" aria-pressed="false">
                    <?php echo htmlspecialchars($filterStatus); ?>
                    <span class="nq-filter-count"><?php echo $statusCounts[$filterStatus]; ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="nq-list" id="requestList">
            <?php foreach ($my_requests as $index => $row): ?>
                <?php
                    $status = $row['status'];
                    $statusClass = 'nq-st--' . strtolower(str_replace(' ', '-', $status));
                    $category = $row['category'];
                    $categoryIcon = $categoryIcons[$category] ?? 'bi-box';
                    $requestedOn = !empty($row['request_date']) ? date('d M Y', strtotime($row['request_date'])) : '—';

                    $noteTone = '';
                    $noteIcon = 'bi-info-circle';
                    $noteLabel = '';
                    $noteText = '';
                    $noteExtra = '';

                    if ($status == 'Rejected' && !empty($row['admin_note'])) {
                        $noteTone = 'nq-req-note--danger';
                        $noteIcon = 'bi-x-circle-fill';
                        $noteLabel = 'Reason';
                        $noteText = $row['admin_note'];
                    } elseif ($status == 'On Loan' && !empty($row['condition_note'])) {
                        $noteTone = 'nq-req-note--info';
                        $noteIcon = 'bi-info-circle-fill';
                        $noteLabel = 'Condition';
                        $noteText = $row['condition_note'];
                    } elseif ($status == 'Issued') {
                        $noteTone = '';
                        $noteIcon = 'bi-clipboard-check-fill';
                        $noteLabel = 'Note';
                        $noteText = 'Handed over for permanent use.';
                        $noteExtra = $row['condition_note'] ?? '';
                    } elseif ($status == 'Returned' && !empty($row['return_note'])) {
                        $noteTone = 'nq-req-note--ok';
                        $noteIcon = 'bi-check-circle-fill';
                        $noteLabel = 'Return note';
                        $noteText = $row['return_note'];
                    } elseif ($status == 'Approved') {
                        $noteTone = 'nq-req-note--ok';
                        $noteIcon = 'bi-bell-fill';
                        $noteLabel = 'Ready';
                        $noteText = 'Head to the ICT Desk for collection.';
                    } elseif ($status == 'Pending') {
                        $noteTone = 'nq-req-note--warn';
                        $noteIcon = 'bi-hourglass-split';
                        $noteLabel = 'Pending';
                        $noteText = 'Waiting for approval from the ICT Department.';
                    } else {
                        $noteLabel = '';
                        $noteText = 'No additional details.';
                    }
                ?>
                <article class="nq-req" data-status="<?php echo htmlspecialchars($status, ENT_QUOTES); ?>" style="--d: <?php echo (int) ($index * 40); ?>;">
                    <div class="nq-req-main">
                        <span class="nq-req-ico"><i class="bi <?php echo $categoryIcon; ?>" aria-hidden="true"></i></span>
                        <div>
                            <h2 class="nq-req-name"><?php echo htmlspecialchars($row['asset_name']); ?></h2>
                            <p class="nq-req-meta">
                                <span><?php echo htmlspecialchars($category); ?></span>
                                <?php if (!empty($row['unique_tag'])): ?>
                                    <span class="nq-sep" aria-hidden="true">&middot;</span>
                                    <span class="nq-req-tag"><i class="bi bi-tag-fill" aria-hidden="true"></i><?php echo htmlspecialchars($row['unique_tag']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <dl class="nq-req-facts">
                        <div>
                            <dt>Qty</dt>
                            <dd><?php echo (int) $row['quantity']; ?></dd>
                        </div>
                        <div>
                            <dt>Requested</dt>
                            <dd><?php echo $requestedOn; ?></dd>
                        </div>
                    </dl>

                    <div class="nq-req-status">
                        <span class="nq-st <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </div>

                    <div class="nq-req-note <?php echo $noteTone; ?>">
                        <i class="bi <?php echo $noteIcon; ?>" aria-hidden="true"></i>
                        <div>
                            <?php if ($noteLabel !== ''): ?><strong><?php echo $noteLabel; ?>:</strong> <?php endif; ?>
                            <?php echo htmlspecialchars($noteText); ?>
                            <?php if (!empty($noteExtra)): ?>
                                <span class="d-block mt-1"><?php echo htmlspecialchars($noteExtra); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="nq-empty is-filtered" id="filterEmpty" hidden>
            <span class="nq-empty-icon"><i class="bi bi-funnel" aria-hidden="true"></i></span>
            <h2>Nothing here</h2>
            <p>No requests with this status yet.</p>
            <button type="button" class="nq-btn nq-btn--ghost" data-filter="all">Show all requests</button>
        </div>
    <?php else: ?>
        <div class="nq-empty">
            <span class="nq-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
            <h2>No borrowing history yet</h2>
            <p>You have not submitted any equipment requests. Browse the inventory to start one.</p>
            <a href="inventory_list.php" class="nq-btn nq-btn--primary">Browse inventory</a>
        </div>
    <?php endif; ?>
</main>

<script>
    (function () {
        var list = document.getElementById('requestList');
        if (!list) return;

        var buttons = document.querySelectorAll('[data-filter]');
        var cards = list.querySelectorAll('[data-status]');
        var emptyState = document.getElementById('filterEmpty');

        function applyFilter(value) {
            buttons.forEach(function (button) {
                var isActive = button.dataset.filter === value;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            var visible = 0;
            cards.forEach(function (card) {
                var show = value === 'all' || card.dataset.status === value;
                card.hidden = !show;
                if (show) visible++;
            });

            if (emptyState) emptyState.hidden = visible !== 0;
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                applyFilter(button.dataset.filter);
            });
        });
    })();
</script>

<?php include 'includes/footer.php'; ?>

</body>
</html>
