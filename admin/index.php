<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// 1. Basic Stats
$total_assets = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$pending_requests = $pdo->query("SELECT COUNT(*) FROM borrowing_requests WHERE status = 'Pending'")->fetchColumn();
$low_stock_count = $pdo->query("SELECT COUNT(*) FROM assets WHERE current_stock > 0 AND current_stock <= 5")->fetchColumn();
$out_of_stock_count = $pdo->query("SELECT COUNT(*) FROM assets WHERE current_stock = 0")->fetchColumn();

$overdue_count = $pdo->query("SELECT COUNT(*) FROM borrowing_requests WHERE status = 'On Loan' AND return_date < CURDATE()")->fetchColumn();
$healthy_stock_count = $total_assets - ($low_stock_count + $out_of_stock_count);

// 2. Data for Category Bar Chart
$cat_query = $pdo->query("SELECT category, COUNT(*) as count FROM assets GROUP BY category ORDER BY count DESC, category ASC");
$categories = [];
$counts = [];
while($row = $cat_query->fetch()) {
    $categories[] = $row['category'];
    $counts[] = (int) $row['count'];
}
$category_count = count($categories);
$max_category_count = $counts ? max($counts) : 0;

// Items that need restocking
$watchlist = $pdo->query("SELECT asset_id, asset_name, category, current_stock 
                          FROM assets 
                          WHERE current_stock <= 5 
                          ORDER BY current_stock ASC, asset_name ASC 
                          LIMIT 5")->fetchAll();
$watchlist_total = $low_stock_count + $out_of_stock_count;

// Fetch Pending OR Overdue requests (Limited to 5)
$query = "SELECT b.request_id, b.request_date, b.return_date, b.quantity, b.status, u.full_name, a.asset_name 
          FROM borrowing_requests b 
          JOIN users u ON b.user_id = u.user_id 
          JOIN assets a ON b.asset_id = a.asset_id 
          WHERE b.status = 'Pending' 
          OR (b.status = 'On Loan' AND b.return_date < CURDATE())
          ORDER BY (CASE WHEN b.status = 'On Loan' THEN 1 ELSE 2 END), b.request_date DESC 
          LIMIT 5";
$pending_list = $pdo->query($query)->fetchAll();
$attention_total = $pending_requests + $overdue_count;
$today_label = date('l, j F Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuqtah Admin - Dashboard</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --sidebar-width: 260px;
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

    body {
        background-color: var(--nq-paper);
        color: var(--nq-ink);
        font-family: var(--nq-font);
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
    }

    /* ---------- Shell ---------- */
    .adm-main {
        padding: 1.25rem 1rem 3rem;
        transition: margin-left 0.3s ease;
    }
    @media (min-width: 992px) {
        .adm-main { margin-left: var(--sidebar-width); padding: 2rem 2.25rem 3.5rem; }
    }

    .adm-wrap { max-width: 1180px; margin: 0 auto; }

    /* ---------- Header ---------- */
    .adm-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem 1.5rem;
        margin-bottom: 1.5rem;
    }
    .adm-head-copy { display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0; }
    .adm-menu-btn {
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        width: 2.6rem;
        height: 2.6rem;
        margin-top: 0.1rem;
        border: 1px solid var(--nq-line);
        border-radius: 11px;
        background: #fff;
        color: var(--nq-deep);
        font-size: 1.3rem;
        box-shadow: 0 8px 18px -14px rgba(0, 77, 64, 0.7);
    }
    .adm-menu-btn:hover { background: var(--nq-tint); color: var(--nq-teal); }

    .adm-eyebrow {
        margin: 0;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--nq-teal);
    }
    .adm-title {
        margin: 0.3rem 0 0.25rem;
        font-size: clamp(1.7rem, 3.2vw, 2.2rem);
        font-weight: 600;
        letter-spacing: -0.025em;
        line-height: 1.1;
        color: var(--nq-deep);
    }
    .adm-sub { margin: 0; font-size: 0.88rem; color: var(--nq-body); }

    .adm-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .adm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.55rem 0.9rem;
        border: 1px solid transparent;
        border-radius: 10px;
        font-family: inherit;
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 1.2;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    }
    .adm-btn i { font-size: 0.95rem; }
    .adm-btn--ghost { border-color: var(--nq-line); background: #fff; color: var(--nq-deep); }
    .adm-btn--ghost:hover { border-color: rgba(0, 121, 107, 0.4); background: var(--nq-tint); color: var(--nq-teal); }
    .adm-btn--primary { background: var(--nq-teal); color: #fff; }
    .adm-btn--primary:hover { background: var(--nq-deep); color: #fff; transform: translateY(-1px); }

    /* ---------- KPI tiles ---------- */
    .adm-kpis {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: minmax(0, 1fr);
        margin-bottom: 1.25rem;
    }
    .adm-kpi {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 1rem 1.05rem;
        border: 1px solid var(--nq-line);
        border-radius: 16px;
        background: #fff;
        text-decoration: none;
        color: inherit;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }
    .adm-kpi:hover { border-color: rgba(0, 121, 107, 0.32); color: inherit; transform: translateY(-2px); box-shadow: 0 18px 32px -28px rgba(0, 77, 64, 0.8); }
    .adm-kpi-top { display: flex; align-items: center; gap: 0.5rem; }
    .adm-kpi-icon {
        display: grid;
        place-items: center;
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 9px;
        background: var(--nq-tint);
        color: var(--nq-teal);
        font-size: 0.95rem;
    }
    .adm-kpi-label {
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--nq-body);
    }
    .adm-kpi-value {
        font-size: 2rem;
        font-weight: 600;
        line-height: 1;
        letter-spacing: -0.02em;
        color: var(--nq-deep);
        font-variant-numeric: tabular-nums;
    }
    .adm-kpi-foot { font-size: 0.78rem; color: var(--nq-body); }
    .adm-kpi.is-warn .adm-kpi-icon { background: #FFF4D6; color: #7A4E00; }
    .adm-kpi.is-warn .adm-kpi-value { color: #7A4E00; }
    .adm-kpi.is-danger .adm-kpi-icon { background: #FDECEA; color: var(--nq-out); }
    .adm-kpi.is-danger .adm-kpi-value { color: var(--nq-out); }
    .adm-kpi.is-danger .adm-kpi-label { color: #8A3B34; }

    /* ---------- Panels ---------- */
    .adm-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
    }
    .adm-panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
        padding: 1.1rem 1.15rem;
        border: 1px solid var(--nq-line);
        border-radius: 18px;
        background: #fff;
    }
    .adm-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.9rem;
    }
    .adm-panel-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        color: var(--nq-deep);
    }
    .adm-panel-sub { margin: 0.15rem 0 0; font-size: 0.78rem; color: var(--nq-body); }
    .adm-panel-link {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--nq-teal);
        text-decoration: none;
        white-space: nowrap;
    }
    .adm-panel-link:hover { text-decoration: underline; text-underline-offset: 3px; }
    .adm-count {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.75rem;
        height: 1.75rem;
        padding: 0 0.45rem;
        border-radius: 999px;
        background: var(--nq-tint);
        color: var(--nq-teal);
        font-size: 0.78rem;
        font-weight: 700;
    }

    /* ---------- Attention list ---------- */
    .adm-tasks { display: grid; gap: 0.5rem; }
    .adm-task {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.7rem;
        padding: 0.65rem 0.7rem;
        border: 1px solid var(--nq-line);
        border-radius: 12px;
        background: #fff;
        text-decoration: none;
        color: inherit;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }
    .adm-task:hover { border-color: rgba(0, 121, 107, 0.32); background: #FAFDFC; color: inherit; }
    .adm-task-icon {
        display: grid;
        place-items: center;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        font-size: 0.85rem;
    }
    .adm-task-icon.is-danger { background: #FDECEA; color: var(--nq-out); }
    .adm-task-icon.is-warn { background: #FFF4D6; color: #7A4E00; }
    .adm-task-main { min-width: 0; }
    .adm-task-main strong { display: block; font-size: 0.88rem; font-weight: 600; color: var(--nq-deep); line-height: 1.3; }
    .adm-task-sub {
        display: block;
        margin-top: 0.1rem;
        font-size: 0.76rem;
        color: var(--nq-body);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .adm-task-side { display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem; text-align: right; }
    .adm-task-date { font-size: 0.72rem; color: var(--nq-body); white-space: nowrap; }
    .adm-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.22rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .adm-badge.is-danger { background: #FDECEA; color: var(--nq-out); }
    .adm-badge.is-warn { background: #FFF4D6; color: #7A4E00; }

    /* ---------- Donut / stock health ---------- */
    .adm-health { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem 1.5rem; margin: auto 0; }
    .adm-donut { position: relative; width: 170px; height: 170px; flex: 0 0 auto; }
    .adm-donut-center {
        position: absolute;
        inset: 0;
        display: grid;
        place-content: center;
        text-align: center;
        pointer-events: none;
    }
    .adm-donut-center b { display: block; font-size: 1.55rem; font-weight: 600; line-height: 1; color: var(--nq-deep); }
    .adm-donut-center span { display: block; margin-top: 0.15rem; font-size: 0.68rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--nq-body); }
    .adm-legend { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.55rem; min-width: 150px; flex: 1 1 auto; }
    .adm-legend li { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--nq-body); }
    .adm-legend b { margin-left: auto; color: var(--nq-deep); font-weight: 600; font-variant-numeric: tabular-nums; }
    .adm-dot { width: 0.55rem; height: 0.55rem; border-radius: 50%; flex: 0 0 auto; }

    /* ---------- Category bars ---------- */
    .adm-cats { display: grid; gap: 0.7rem; }
    .adm-cat-top { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.3rem; }
    .adm-cat-name { font-size: 0.85rem; font-weight: 500; color: var(--nq-deep); }
    .adm-cat-name span { color: var(--nq-body); font-weight: 400; font-size: 0.78rem; }
    .adm-cat-count { font-size: 0.82rem; font-weight: 600; color: var(--nq-deep); font-variant-numeric: tabular-nums; }
    .adm-cat-bar { height: 6px; border-radius: 999px; background: rgba(0, 77, 64, 0.08); overflow: hidden; }
    .adm-cat-bar span { display: block; height: 100%; border-radius: inherit; background: var(--nq-teal); }
    .adm-cat:first-child .adm-cat-bar span { background: var(--nq-deep); }

    /* ---------- Watchlist ---------- */
    .adm-watch { display: grid; gap: 0.15rem; }
    .adm-watch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.5rem;
        border-radius: 10px;
        text-decoration: none;
        color: inherit;
    }
    .adm-watch-row:hover { background: #F7FAF9; color: inherit; }
    .adm-watch-name { min-width: 0; font-size: 0.85rem; font-weight: 500; color: var(--nq-deep); }
    .adm-watch-name span {
        display: block;
        font-size: 0.72rem;
        font-weight: 400;
        color: var(--nq-body);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .adm-watch-qty {
        flex: 0 0 auto;
        padding: 0.22rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        background: #FFF4D6;
        color: #7A4E00;
        white-space: nowrap;
    }
    .adm-watch-qty.is-out { background: #FDECEA; color: var(--nq-out); }

    /* ---------- Empty states ---------- */
    .adm-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
        padding: 1.75rem 1rem;
        text-align: center;
    }
    .adm-empty-icon {
        display: grid;
        place-items: center;
        width: 2.5rem;
        height: 2.5rem;
        margin-bottom: 0.4rem;
        border-radius: 50%;
        background: var(--nq-tint);
        color: var(--nq-teal);
        font-size: 1.1rem;
    }
    .adm-empty strong { font-size: 0.9rem; font-weight: 600; color: var(--nq-deep); }
    .adm-empty span { font-size: 0.8rem; color: var(--nq-body); }

    /* ---------- Focus ---------- */
    .adm-main a:focus-visible,
    .adm-main button:focus-visible {
        outline: 2px solid var(--nq-teal);
        outline-offset: 3px;
    }

    /* ---------- Responsive ---------- */
    @media (min-width: 560px) {
        .adm-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1200px) {
        .adm-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; }
        .adm-grid { grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); }
    }
    @media (max-width: 575.98px) {
        .adm-btn { font-size: 0.8rem; padding: 0.5rem 0.7rem; }
        .adm-task { grid-template-columns: auto minmax(0, 1fr); }
        .adm-task-side { grid-column: 2; flex-direction: row; align-items: center; justify-content: space-between; width: 100%; }
        .adm-task-sub { white-space: normal; }
    }
    @media (max-width: 559.98px) {
        .adm-kpi { flex-direction: row; align-items: center; gap: 0.75rem; padding: 0.7rem 0.85rem; }
        .adm-kpi-top { flex: 1 1 auto; min-width: 0; }
        .adm-kpi-value { font-size: 1.5rem; }
        .adm-kpi-foot { display: none; }
        .adm-health { flex-direction: column; align-items: stretch; gap: 1.25rem; }
        .adm-donut { align-self: center; }
    }
    @media (max-width: 349.98px) {
        .adm-btn span { display: none; }
        .adm-btn { width: 2.6rem; height: 2.6rem; padding: 0; }
    }
    @media (hover: hover) {
        .adm-panel { transition: box-shadow 0.2s ease; }
    }
    @media (prefers-reduced-motion: reduce) {
        .adm-main, .adm-kpi, .adm-btn { transition: none !important; }
        .adm-kpi:hover, .adm-btn--primary:hover { transform: none; }
    }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="adm-main">
    <div class="adm-wrap">

        <header class="adm-head">
            <div class="adm-head-copy">
                <button type="button" id="menuToggle" class="adm-menu-btn d-lg-none" aria-label="Open navigation" aria-controls="adminSidebar" aria-expanded="false">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div>
                    <p class="adm-eyebrow">ITQSHHB &middot; ICT Department</p>
                    <h1 class="adm-title">Dashboard</h1>
                    <p class="adm-sub"><?php echo $today_label; ?> &middot; live inventory status</p>
                </div>
            </div>
            <div class="adm-actions">
                <a href="report.php" class="adm-btn adm-btn--ghost" aria-label="Open reports">
                    <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i><span>Report</span>
                </a>
                <a href="add_assets.php" class="adm-btn adm-btn--ghost" aria-label="Add a new asset">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add asset</span>
                </a>
                <button type="button" class="adm-btn adm-btn--primary" onclick="window.location.reload()" aria-label="Refresh dashboard data">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span>Refresh</span>
                </button>
            </div>
        </header>

        <!-- KPI tiles -->
        <div class="adm-kpis">
            <a class="adm-kpi" href="manage_assets.php">
                <span class="adm-kpi-top">
                    <span class="adm-kpi-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                    <span class="adm-kpi-label">Total assets</span>
                </span>
                <span class="adm-kpi-value"><?php echo (int) $total_assets; ?></span>
                <span class="adm-kpi-foot">Across <?php echo $category_count; ?> <?php echo $category_count === 1 ? 'category' : 'categories'; ?></span>
            </a>

            <a class="adm-kpi <?php echo ($pending_requests > 0) ? 'is-warn' : ''; ?>" href="view_requests.php">
                <span class="adm-kpi-top">
                    <span class="adm-kpi-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                    <span class="adm-kpi-label">Pending requests</span>
                </span>
                <span class="adm-kpi-value"><?php echo (int) $pending_requests; ?></span>
                <span class="adm-kpi-foot"><?php echo ($pending_requests > 0) ? 'Waiting for your approval' : 'Nothing waiting'; ?></span>
            </a>

            <a class="adm-kpi <?php echo ($overdue_count > 0) ? 'is-danger' : ''; ?>" href="view_requests.php">
                <span class="adm-kpi-top">
                    <span class="adm-kpi-icon"><i class="bi bi-calendar-x" aria-hidden="true"></i></span>
                    <span class="adm-kpi-label">Overdue loans</span>
                </span>
                <span class="adm-kpi-value"><?php echo (int) $overdue_count; ?></span>
                <span class="adm-kpi-foot"><?php echo ($overdue_count > 0) ? 'Past the return date' : 'None overdue'; ?></span>
            </a>

            <a class="adm-kpi <?php echo ($out_of_stock_count > 0) ? 'is-danger' : ''; ?>" href="manage_assets.php">
                <span class="adm-kpi-top">
                    <span class="adm-kpi-icon"><i class="bi bi-x-circle" aria-hidden="true"></i></span>
                    <span class="adm-kpi-label">Out of stock</span>
                </span>
                <span class="adm-kpi-value"><?php echo (int) $out_of_stock_count; ?></span>
                <span class="adm-kpi-foot"><?php echo (int) $low_stock_count; ?> low on stock</span>
            </a>
        </div>

        <div class="adm-grid">

            <!-- Needs attention -->
            <section class="adm-panel" aria-labelledby="attentionTitle">
                <div class="adm-panel-head">
                    <div>
                        <h2 class="adm-panel-title" id="attentionTitle">Needs attention</h2>
                        <p class="adm-panel-sub">Pending approvals and loans past their return date.</p>
                    </div>
                    <?php if ($attention_total > 0): ?>
                        <span class="adm-count" aria-label="<?php echo (int) $attention_total; ?> items need attention"><?php echo (int) $attention_total; ?></span>
                    <?php endif; ?>
                </div>

                <?php if (count($pending_list) > 0): ?>
                    <div class="adm-tasks">
                        <?php foreach ($pending_list as $request): ?>
                            <?php
                                $isOverdue = ($request['status'] == 'On Loan' && date('Y-m-d') > $request['return_date']);
                                $daysLate = 0;
                                if ($isOverdue) {
                                    $daysLate = max(1, (int) floor((strtotime(date('Y-m-d')) - strtotime($request['return_date'])) / 86400));
                                }
                            ?>
                            <a class="adm-task" href="view_requests.php">
                                <span class="adm-task-icon <?php echo $isOverdue ? 'is-danger' : 'is-warn'; ?>">
                                    <i class="bi <?php echo $isOverdue ? 'bi-exclamation-triangle-fill' : 'bi-hourglass'; ?>" aria-hidden="true"></i>
                                </span>
                                <span class="adm-task-main">
                                    <strong><?php echo htmlspecialchars($request['full_name']); ?></strong>
                                    <span class="adm-task-sub">
                                        <?php echo htmlspecialchars($request['asset_name']); ?> &middot; <?php echo (int) $request['quantity']; ?> unit<?php echo ((int) $request['quantity'] === 1) ? '' : 's'; ?>
                                    </span>
                                </span>
                                <span class="adm-task-side">
                                    <?php if ($isOverdue): ?>
                                        <span class="adm-badge is-danger">Overdue <?php echo $daysLate; ?>d</span>
                                        <span class="adm-task-date">Due <?php echo date('d M', strtotime($request['return_date'])); ?></span>
                                    <?php else: ?>
                                        <span class="adm-badge is-warn">Pending</span>
                                        <span class="adm-task-date">Asked <?php echo date('d M', strtotime($request['request_date'])); ?></span>
                                    <?php endif; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <span class="adm-panel-sub">Showing <?php echo count($pending_list); ?> of <?php echo (int) $attention_total; ?></span>
                        <a href="view_requests.php" class="adm-panel-link">All requests <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                <?php else: ?>
                    <div class="adm-empty">
                        <span class="adm-empty-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                        <strong>All caught up</strong>
                        <span>No approvals or late returns waiting.</span>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Stock health -->
            <section class="adm-panel" aria-labelledby="healthTitle">
                <div class="adm-panel-head">
                    <div>
                        <h2 class="adm-panel-title" id="healthTitle">Stock health</h2>
                        <p class="adm-panel-sub">How the catalogue is holding up.</p>
                    </div>
                    <a href="manage_assets.php" class="adm-panel-link">Manage</a>
                </div>

                <?php if ((int) $total_assets > 0): ?>
                    <div class="adm-health">
                        <div class="adm-donut">
                            <canvas id="stockChart" aria-label="Stock health distribution" role="img"></canvas>
                            <div class="adm-donut-center">
                                <b><?php echo (int) $total_assets; ?></b>
                                <span>assets</span>
                            </div>
                        </div>
                        <ul class="adm-legend">
                            <li><span class="adm-dot" style="background:#00796B"></span>Healthy <b><?php echo (int) $healthy_stock_count; ?></b></li>
                            <li><span class="adm-dot" style="background:#E08A00"></span>Low stock <b><?php echo (int) $low_stock_count; ?></b></li>
                            <li><span class="adm-dot" style="background:#D93025"></span>Out of stock <b><?php echo (int) $out_of_stock_count; ?></b></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="adm-empty">
                        <span class="adm-empty-icon"><i class="bi bi-box" aria-hidden="true"></i></span>
                        <strong>No assets yet</strong>
                        <span>Add your first asset to see stock health.</span>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Categories -->
            <section class="adm-panel" aria-labelledby="categoryTitle">
                <div class="adm-panel-head">
                    <div>
                        <h2 class="adm-panel-title" id="categoryTitle">Inventory by category</h2>
                        <p class="adm-panel-sub"><?php echo (int) $total_assets; ?> assets in total.</p>
                    </div>
                    <a href="manage_assets.php" class="adm-panel-link">Manage</a>
                </div>

                <?php if ($category_count > 0): ?>
                    <div class="adm-cats">
                        <?php foreach ($categories as $index => $category_name): ?>
                            <?php
                                $value = (int) $counts[$index];
                                $width = $max_category_count > 0 ? max(3, round(($value / $max_category_count) * 100)) : 0;
                            ?>
                            <div class="adm-cat">
                                <div class="adm-cat-top">
                                    <span class="adm-cat-name"><?php echo htmlspecialchars($category_name); ?></span>
                                    <span class="adm-cat-count"><?php echo $value; ?></span>
                                </div>
                                <div class="adm-cat-bar" role="presentation"><span style="width: <?php echo $width; ?>%"></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="adm-empty">
                        <span class="adm-empty-icon"><i class="bi bi-grid" aria-hidden="true"></i></span>
                        <strong>Nothing catalogued</strong>
                        <span>Categories appear once assets are added.</span>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Watchlist -->
            <section class="adm-panel" aria-labelledby="watchTitle">
                <div class="adm-panel-head">
                    <div>
                        <h2 class="adm-panel-title" id="watchTitle">Restock watchlist</h2>
                        <p class="adm-panel-sub">Items at five units or fewer.</p>
                    </div>
                    <?php if ($watchlist_total > 0): ?>
                        <a href="manage_assets.php" class="adm-panel-link">All <?php echo (int) $watchlist_total; ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    <?php endif; ?>
                </div>

                <?php if (count($watchlist) > 0): ?>
                    <div class="adm-watch">
                        <?php foreach ($watchlist as $item): ?>
                            <a class="adm-watch-row" href="manage_assets.php">
                                <span class="adm-watch-name">
                                    <?php echo htmlspecialchars($item['asset_name']); ?>
                                    <span><?php echo htmlspecialchars($item['category']); ?></span>
                                </span>
                                <span class="adm-watch-qty <?php echo ((int) $item['current_stock'] === 0) ? 'is-out' : ''; ?>">
                                    <?php echo (int) $item['current_stock'] === 0 ? 'Out' : ((int) $item['current_stock'] . ' left'); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="adm-empty">
                        <span class="adm-empty-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                        <strong>Stock looks healthy</strong>
                        <span>No item is running low right now.</span>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var canvas = document.getElementById('stockChart');
        if (!canvas || typeof Chart === 'undefined') return;

        new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Low stock', 'Out of stock'],
                datasets: [{
                    data: [<?php echo (int) $healthy_stock_count; ?>, <?php echo (int) $low_stock_count; ?>, <?php echo (int) $out_of_stock_count; ?>],
                    backgroundColor: ['#00796B', '#E08A00', '#D93025'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#004D40',
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.parsed;
                            }
                        }
                    }
                }
            }
        });
    })();
</script>

</body>
</html>
