<?php
session_start();
// This file creates the $pdo object
include 'includes/db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'Staff';
$user_id = $_SESSION['user_id']; 

// --- NEW NOTIFICATION LOGIC START ---
$notifQuery = "SELECT r.*, a.asset_name 
                FROM borrowing_requests r 
                JOIN assets a ON r.asset_id = a.asset_id 
                WHERE r.user_id = ? 
                AND r.is_read = 0 
                AND r.status != 'Pending'
                ORDER BY r.request_date DESC LIMIT 1";
$notifStmt = $pdo->prepare($notifQuery);
$notifStmt->execute([$user_id]);
$latest_request = $notifStmt->fetch();

$show_status_modal = isset($_SESSION['first_login_session']) && $_SESSION['first_login_session'] && $latest_request;
// --- NEW NOTIFICATION LOGIC END ---

$searchTerm = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? 'all');
$allowedCategories = ['Laptops', 'Projectors', 'Accessories', 'Consumables', 'Others'];
if ($categoryFilter !== 'all' && !in_array($categoryFilter, $allowedCategories, true)) {
    $categoryFilter = 'all';
}

$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$whereClauses = [];
$queryParams = [];

if ($searchTerm !== '') {
    $whereClauses[] = '(asset_name LIKE :search_name OR category LIKE :search_category)';
    $queryParams[':search_name'] = "%{$searchTerm}%";
    $queryParams[':search_category'] = "%{$searchTerm}%";
}

if ($categoryFilter !== 'all') {
    $whereClauses[] = 'category = :category';
    $queryParams[':category'] = $categoryFilter;
}

$whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM assets {$whereSql}");
foreach ($queryParams as $param => $value) {
    $countStmt->bindValue($param, $value, PDO::PARAM_STR);
}
$countStmt->execute();
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $itemsPerPage;

$stmt = $pdo->prepare("SELECT * FROM assets {$whereSql} ORDER BY asset_name ASC LIMIT :limit OFFSET :offset");
foreach ($queryParams as $param => $value) {
    $stmt->bindValue($param, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$assets = $stmt->fetchAll();

$firstItem = $totalItems > 0 ? $offset + 1 : 0;
$lastItem = min($offset + count($assets), $totalItems);
$rangeLabel = $firstItem === $lastItem ? (string) $firstItem : $firstItem . '–' . $lastItem;
$queryBase = ['search' => $searchTerm, 'category' => $categoryFilter];
$isFiltered = ($searchTerm !== '' || $categoryFilter !== 'all');

// Builds a pagination URL that keeps the active search and category filters.
$pageUrl = function (int $target) use ($searchTerm, $categoryFilter): string {
    $params = ['page' => $target];
    if ($searchTerm !== '') {
        $params['search'] = $searchTerm;
    }
    if ($categoryFilter !== 'all') {
        $params['category'] = $categoryFilter;
    }
    return '?' . http_build_query($params);
};

// Compact page window: 1 ... 4 5 6 ... 12
$pageNumbers = [];
if ($totalPages <= 7) {
    for ($p = 1; $p <= $totalPages; $p++) {
        $pageNumbers[] = $p;
    }
} else {
    $pageNumbers[] = 1;
    if ($page > 4) {
        $pageNumbers[] = '...';
    }
    $windowStart = max(2, $page - 1);
    $windowEnd = min($totalPages - 1, $page + 1);
    for ($p = $windowStart; $p <= $windowEnd; $p++) {
        $pageNumbers[] = $p;
    }
    if ($page < $totalPages - 3) {
        $pageNumbers[] = '...';
    }
    $pageNumbers[] = $totalPages;
}
$categoryLabels = [
    'all' => 'All equipment',
    'Laptops' => 'Laptops',
    'Projectors' => 'Projectors',
    'Accessories' => 'Accessories',
    'Consumables' => 'Consumables',
    'Others' => 'Others',
];
$categoryIcons = [
    'all' => 'bi-grid-3x3-gap',
    'Laptops' => 'bi-laptop',
    'Projectors' => 'bi-projector',
    'Accessories' => 'bi-plug',
    'Consumables' => 'bi-box-seam',
    'Others' => 'bi-three-dots',
];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuqtah - Inventory List</title>
    <link rel="icon" type="image/png" href="/Nuqtah_IT/assets/img/Nuqtah_logo_small.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* ---------------------------------------------------------------
           Nuqtah inventory browser - page scoped styles
           Keeps the site's teal palette, rebuilds the layout to be fluid.
        --------------------------------------------------------------- */
        :root {
            --brand-teal: #00796B;
            --brand-teal-dark: #005a4d;
            --brand-soft: #edf7f4;

            --nq-teal: #00796B;
            --nq-deep: #004D40;
            --nq-accent: #A5D6A7;
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

        body.nq-inv {
            background-color: var(--nq-paper);
            color: var(--nq-ink);
            font-family: var(--nq-font);
            -webkit-font-smoothing: antialiased;
        }

        .nq-shell {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 1.75rem 1rem 4.5rem;
        }

        /* ---------- Page head ---------- */
        .nq-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 0.75rem 2rem;
            margin-bottom: 1.5rem;
        }
        .nq-head-copy { max-width: 620px; }
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
            font-size: clamp(1.85rem, 4.4vw, 2.6rem);
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1.1;
            color: var(--nq-deep);
        }
        .nq-lede {
            margin: 0;
            max-width: 52ch;
            font-size: 0.95rem;
            color: var(--nq-body);
        }
        .nq-count {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }
        .nq-count b {
            font-size: 2rem;
            line-height: 1;
            font-weight: 600;
            color: var(--nq-teal);
            font-variant-numeric: tabular-nums;
        }
        .nq-count span {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--nq-body);
        }

        /* ---------- Notification banner ---------- */
        .nq-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.1rem;
            border: 1px solid var(--nq-line);
            border-radius: 16px;
            background: #fff;
            color: var(--nq-deep);
        }
        .nq-alert.alert-dismissible { padding-right: 3.25rem; }
        .nq-alert .btn-close {
            position: absolute;
            top: 0.95rem;
            right: 0.95rem;
            padding: 0.35rem;
            font-size: 0.7rem;
        }
        .nq-alert-icon {
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 50%;
            background: #fff;
            font-size: 1.15rem;
        }
        .nq-alert-copy strong { display: block; font-size: 0.95rem; font-weight: 600; }
        .nq-alert-copy p { margin: 0.2rem 0 0.5rem; font-size: 0.88rem; color: var(--nq-body); }
        .nq-alert-copy p strong { display: inline; font-size: inherit; }
        .nq-alert-link {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--nq-teal);
            text-decoration: none;
        }
        .nq-alert-link:hover { text-decoration: underline; text-underline-offset: 3px; }
        .nq-alert--ok { background: #F1F9F4; border-color: rgba(20, 108, 67, 0.22); }
        .nq-alert--ok .nq-alert-icon { color: var(--nq-ok); }
        .nq-alert--danger { background: #FCF3F2; border-color: rgba(179, 38, 30, 0.22); }
        .nq-alert--danger .nq-alert-icon { color: var(--nq-out); }
        .nq-alert--info { background: #F1F6F9; border-color: rgba(13, 71, 110, 0.18); }
        .nq-alert--info .nq-alert-icon { color: #0D476E; }

        /* ---------- Toolbar: search + filters ---------- */
        .nq-toolbar {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            margin-bottom: 1.25rem;
            padding: 1rem;
            border: 1px solid var(--nq-line);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 20px 40px -34px rgba(0, 77, 64, 0.5);
        }
        .nq-search {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.35rem 0.4rem 0.35rem 0.85rem;
            border: 1px solid transparent;
            border-radius: 14px;
            background: #F4F8F7;
            transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .nq-search:focus-within {
            background: #fff;
            border-color: rgba(0, 121, 107, 0.45);
            box-shadow: 0 0 0 4px rgba(0, 121, 107, 0.1);
        }
        .nq-search-ico { flex: 0 0 auto; color: var(--nq-body); font-size: 1rem; }
        .nq-search-input {
            flex: 1 1 auto;
            min-width: 0;
            padding: 0.5rem 0;
            border: 0;
            background: transparent;
            color: var(--nq-ink);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
        }
        .nq-search-input::placeholder { color: rgba(0, 77, 64, 0.45); }
        .nq-search .nq-btn { flex: 0 0 auto; }

        .nq-filter { display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0; }
        .nq-filter-label {
            flex: 0 0 auto;
            margin-bottom: 0;
            padding-top: 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--nq-body);
        }
        .nq-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            min-width: 0;
        }
        .nq-chip {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.48rem 0.85rem;
            border: 1px solid var(--nq-line);
            border-radius: 999px;
            background: #fff;
            color: var(--nq-deep);
            font-size: 0.86rem;
            font-weight: 500;
            line-height: 1.2;
            text-decoration: none;
            white-space: nowrap;
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }
        .nq-chip i { font-size: 0.9rem; opacity: 0.85; }
        .nq-chip:hover {
            border-color: rgba(0, 121, 107, 0.4);
            background: var(--nq-tint);
            color: var(--nq-teal);
        }
        .nq-chip.is-active {
            border-color: var(--nq-teal);
            background: var(--nq-teal);
            color: #fff;
        }
        .nq-chip.is-active i { opacity: 1; }

        /* ---------- Result meta ---------- */
        .nq-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.84rem;
            color: var(--nq-body);
        }
        .nq-meta p { margin: 0; }
        .nq-meta b { color: var(--nq-deep); font-weight: 600; font-variant-numeric: tabular-nums; }
        .nq-meta a {
            color: var(--nq-teal);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid rgba(0, 121, 107, 0.35);
        }
        .nq-meta a:hover { border-bottom-color: var(--nq-teal); }

        /* ---------- Card grid ---------- */
        .nq-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }

        .nq-card {
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            border: 1px solid var(--nq-line);
            border-radius: 18px;
            background: #fff;
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease, border-color 0.3s ease;
            animation: nq-rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) backwards;
            animation-delay: calc(var(--d, 0) * 1ms);
        }
        @keyframes nq-rise {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: none; }
        }
        .nq-thumb {
            position: relative;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            background: #fff;
        }
        .nq-thumb img {
            display: block;
            width: 100%;
            height: 100%;
            padding: 0.5rem;
            object-fit: contain;
            transition: transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .nq-card-body {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 0.55rem;
            padding: 0.95rem 1.05rem 1.05rem;
        }
        .nq-card-cat {
            margin: 0;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--nq-body);
        }
        .nq-card-title {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 600;
            line-height: 1.32;
            letter-spacing: -0.01em;
            color: var(--nq-deep);
        }
        .nq-card-link {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            padding: 0;
            border: 0;
            background: none;
            color: inherit;
            font: inherit;
            text-align: left;
            cursor: pointer;
            overflow-wrap: anywhere;
        }
        .nq-card-link:hover { color: var(--nq-teal); }
        .nq-card-link:focus-visible {
            outline: 2px solid var(--nq-teal);
            outline-offset: 3px;
            border-radius: 4px;
        }

        .nq-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin: 0;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .nq-dot {
            width: 0.5rem;
            height: 0.5rem;
            flex: 0 0 auto;
            border-radius: 50%;
            background: currentColor;
        }
        .nq-status.is-ok { color: var(--nq-ok); }
        .nq-status.is-ok .nq-dot { box-shadow: 0 0 0 3px rgba(20, 108, 67, 0.14); }
        .nq-status.is-low { color: var(--nq-low); }
        .nq-status.is-low .nq-dot { box-shadow: 0 0 0 3px rgba(154, 91, 0, 0.14); }
        .nq-status.is-out { color: var(--nq-out); }
        .nq-status.is-out .nq-dot { box-shadow: 0 0 0 3px rgba(179, 38, 30, 0.14); }

        .nq-stock { margin-top: 0.15rem; }
        .nq-stock-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
            font-size: 0.76rem;
            color: var(--nq-body);
        }
        .nq-stock-head b { color: var(--nq-deep); font-weight: 600; font-variant-numeric: tabular-nums; }
        .nq-sep { opacity: 0.45; }
        .nq-meter {
            height: 5px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(0, 77, 64, 0.09);
        }
        .nq-meter > span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--nq-teal);
            transition: width 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .nq-meter.is-low > span { background: #E08A00; }
        .nq-meter.is-out > span { background: #D93025; }

        .nq-card-cta { margin-top: auto; padding-top: 0.45rem; }

        /* ---------- Buttons ---------- */
        .nq-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.62rem 1rem;
            border: 1px solid transparent;
            border-radius: 11px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            line-height: 1.25;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .nq-btn--primary {
            background: var(--nq-teal);
            color: #fff;
            box-shadow: 0 12px 24px -16px rgba(0, 121, 107, 0.9);
        }
        .nq-btn--primary:hover, .nq-btn--primary:focus-visible {
            background: var(--brand-teal-dark);
            color: #fff;
            transform: translateY(-1px);
        }
        .nq-btn--ghost {
            border-color: var(--nq-line);
            background: #fff;
            color: var(--nq-deep);
        }
        .nq-btn--ghost:hover, .nq-btn--ghost:focus-visible {
            border-color: rgba(0, 121, 107, 0.4);
            background: var(--nq-tint);
            color: var(--nq-teal);
        }
        .nq-btn--primary:disabled {
            background: rgba(0, 77, 64, 0.08);
            color: rgba(0, 77, 64, 0.8);
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }
        .nq-card-cta .nq-btn { width: 100%; }

        /* ---------- Empty state ---------- */
        .nq-empty {
            grid-column: 1 / -1;
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
        .nq-empty p { margin: 0 0 0.75rem; max-width: 44ch; font-size: 0.9rem; color: var(--nq-body); }

        /* ---------- Pagination ---------- */
        .nq-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--nq-line);
        }
        .nq-page-list {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .nq-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            min-width: 2.3rem;
            height: 2.3rem;
            padding: 0 0.65rem;
            border: 1px solid var(--nq-line);
            border-radius: 10px;
            background: #fff;
            color: var(--nq-deep);
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            font-variant-numeric: tabular-nums;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
        .nq-page-link:hover { border-color: rgba(0, 121, 107, 0.4); color: var(--nq-teal); }
        .nq-page-link.is-active { border-color: var(--nq-teal); background: var(--nq-teal); color: #fff; }
        .nq-page-link.is-disabled { opacity: 0.4; pointer-events: none; }
        .nq-page-gap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.4rem;
            color: var(--nq-body);
            font-size: 0.84rem;
        }
        .nq-page-step span { display: none; }
        .nq-pager-status { display: none; font-size: 0.82rem; color: var(--nq-body); }

        /* ---------- Floating actions ---------- */
        .nq-fab {
            position: fixed;
            bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
            z-index: 1030;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        .nq-fab.is-hidden {
            opacity: 0;
            transform: translateY(8px);
            pointer-events: none;
        }
        .nq-fab--cart { left: 1rem; }
        .nq-fab--admin { right: 1rem; }
        .nq-fab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.05rem;
            border-radius: 999px;
            background: var(--nq-deep);
            color: #fff;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 18px 36px -20px rgba(0, 77, 64, 0.85);
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .nq-fab-btn i { font-size: 1rem; }
        .nq-fab-btn:hover { background: var(--nq-teal); color: #fff; transform: translateY(-2px); }
        .nq-fab--admin .nq-fab-btn { background: var(--nq-teal); }
        .nq-fab--admin .nq-fab-btn:hover { background: var(--brand-teal-dark); }
        .nq-fab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 0.3rem;
            border-radius: 999px;
            background: #fff;
            color: var(--nq-deep);
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* ---------- Modals ---------- */
        .nq-inv .modal-content,
        .nq-modal {
            border: 0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px -30px rgba(0, 0, 0, 0.35);
            font-family: var(--nq-font);
        }
        .nq-modal .modal-header {
            align-items: flex-start;
            padding: 1.15rem 1.35rem;
            border-bottom: 1px solid var(--nq-line);
        }
        .nq-modal .modal-body { padding: 1.35rem; }
        .nq-modal .modal-footer {
            padding: 0.9rem 1.35rem;
            border-top: 1px solid var(--nq-line);
            gap: 0.5rem;
        }
        .nq-modal-eyebrow {
            margin: 0 0 0.2rem;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--nq-teal);
        }
        .nq-modal-title { margin: 0; font-size: 1.15rem; font-weight: 600; color: var(--nq-deep); }
        .nq-modal-grid { display: grid; gap: 1.15rem; grid-template-columns: minmax(0, 1fr); }
        .nq-modal-media img {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            padding: 0.35rem;
            object-fit: contain;
            border-radius: 14px;
            background: var(--nq-tint);
        }
        .nq-modal-statusline {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 0.75rem;
            margin-bottom: 0.85rem;
        }
        .nq-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(0, 77, 64, 0.08);
            color: var(--nq-deep);
        }
        .nq-pill.is-ok { background: #E8F5E9; color: var(--nq-ok); }
        .nq-pill.is-low { background: #FFF3E0; color: var(--nq-low); }
        .nq-pill.is-out { background: #FDECEA; color: var(--nq-out); }
        .nq-modal-stock { font-size: 0.82rem; color: var(--nq-body); font-variant-numeric: tabular-nums; }
        .nq-meter--lg { height: 6px; margin-bottom: 0.5rem; }
        .nq-modal-note { margin: 0 0 1rem; font-size: 0.8rem; color: var(--nq-body); }
        .nq-tags {
            max-height: 250px;
            overflow: auto;
            padding-top: 0.85rem;
            border-top: 1px solid var(--nq-line);
        }
        .nq-tags .table { margin-bottom: 0; font-size: 0.82rem; }
        .nq-tags .table > :not(caption) > * > * { padding: 0.55rem 0.5rem; }
        .nq-tags .table thead th {
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--nq-body);
        }
        .nq-tags-note { margin: 0; padding: 0.75rem 0; font-size: 0.85rem; color: var(--nq-body); }
        .nq-modal--status .modal-body { padding: 1.75rem 1.5rem 1.5rem; }
        .nq-status-icon {
            display: grid;
            place-items: center;
            width: 4rem;
            height: 4rem;
            margin: 0 auto 0.9rem;
            border-radius: 50%;
            background: var(--nq-tint);
            color: var(--nq-teal);
            font-size: 1.8rem;
        }
        .nq-status-icon.is-ok { background: #E8F5E9; color: var(--nq-ok); }
        .nq-status-icon.is-warn { background: #FFF3E0; color: var(--nq-low); }
        .nq-status-icon.is-danger { background: #FDECEA; color: var(--nq-out); }
        .nq-status-icon.is-info { background: #E9F3F9; color: #0D476E; }
        .nq-modal-text { margin: 0.5rem auto 1.25rem; max-width: 34ch; font-size: 0.9rem; color: var(--nq-body); }

        /* ---------- Focus ---------- */
        .nq-shell a:focus-visible,
        .nq-shell button:focus-visible,
        .nq-modal a:focus-visible,
        .nq-modal button:focus-visible {
            outline: 2px solid var(--nq-teal);
            outline-offset: 3px;
        }

        /* ---------- Responsive ---------- */
        @media (min-width: 560px) {
            .nq-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.1rem; }
        }
        @media (min-width: 768px) {
            .nq-shell { padding: 2.5rem 1.5rem 4.5rem; }
            .nq-head { margin-bottom: 1.75rem; }
            .nq-toolbar { padding: 1.15rem 1.25rem; }
            .nq-page-step span { display: inline; }
            .nq-page-link { height: 2.4rem; }
        }
        @media (min-width: 992px) {
            .nq-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.35rem; }
            .nq-modal-grid { grid-template-columns: 260px minmax(0, 1fr); align-items: start; }
        }
        @media (max-width: 419.98px) {
            .nq-search { padding-left: 0.7rem; }
            .nq-search-ico { display: none; }
            .nq-search-input { font-size: 0.9rem; }
            .nq-search .nq-btn { padding-left: 0.8rem; padding-right: 0.8rem; }
        }
        @media (max-width: 575.98px) {
            .nq-filter { flex-direction: column; align-items: stretch; gap: 0.4rem; }
            .nq-filter-label { margin-left: 0.15rem; padding-top: 0; }
            .nq-page-list { display: none; }
            .nq-pager-status { display: inline; }
            .nq-pager { justify-content: space-between; }
            .nq-fab--admin .nq-fab-label { display: none; }
            .nq-fab--admin .nq-fab-btn { padding: 0.8rem; border-radius: 50%; }
            .nq-fab-btn { font-size: 0.84rem; padding: 0.7rem 0.95rem; }
            .nq-card-body { padding: 0.85rem 0.95rem 0.95rem; }
            .nq-modal .modal-body { padding: 1.1rem; }
            .nq-modal .modal-header, .nq-modal .modal-footer { padding: 1rem 1.1rem; }
        }
        @media (hover: hover) {
            .nq-card:hover {
                transform: translateY(-4px);
                border-color: rgba(0, 121, 107, 0.28);
                box-shadow: 0 20px 38px -26px rgba(0, 77, 64, 0.55);
            }
            .nq-card:hover .nq-thumb img { transform: scale(1.04); }
            .nq-card:hover .nq-card-link { color: var(--nq-teal); }
        }
        @media (prefers-reduced-motion: reduce) {
            .nq-card { animation: none; }
            .nq-card, .nq-thumb img, .nq-btn, .nq-page-link, .nq-chip, .nq-fab-btn,
            .nq-meter > span, .nq-search { transition: none !important; }
            .nq-card:hover, .nq-btn--primary:hover { transform: none; }
            .nq-card:hover .nq-thumb img { transform: none; }
        }
    </style>
</head>
<body class="nq-inv">

<?php include 'includes/header.php'; ?> 
<?php include 'includes/users_sidebar.php'; ?>

<main class="nq-shell">

    <header class="nq-head">
        <div class="nq-head-copy">
            <p class="nq-eyebrow">ITQSHHB &middot; ICT Department</p>
            <h1 class="nq-title">Browse equipment</h1>
            <p class="nq-lede">Request laptops, projectors and accessories from campus stock, then follow every step of the approval.</p>
        </div>
        <div class="nq-count">
            <b><?php echo (int) $totalItems; ?></b>
            <span><?php echo $totalItems === 1 ? 'item' : 'items'; ?><?php echo $isFiltered ? ' matched' : ' in catalogue'; ?></span>
        </div>
    </header>

    <?php if ($latest_request && $latest_request['is_read'] == 0 && $latest_request['status'] !== 'Pending'): ?>
        <?php
            $alertTone = 'info';
            $alertIcon = 'bi-info-circle-fill';
            $instruction = 'Your request is being processed.';

            if ($latest_request['status'] == 'Approved') {
                $alertTone = 'ok';
                $alertIcon = 'bi-check-circle-fill';
                $instruction = 'Please head over to the ICT Department to collect your items.';
            } elseif ($latest_request['status'] == 'Rejected') {
                $alertTone = 'danger';
                $alertIcon = 'bi-exclamation-octagon-fill';
                $instruction = 'Please check the rejection reason in your history.';
            } elseif ($latest_request['status'] == 'On Loan') {
                $alertTone = 'info';
                $alertIcon = 'bi-box-seam-fill';
                $instruction = 'Ensure you handle the equipment with care.';
            }
        ?>
        <div class="alert nq-alert nq-alert--<?php echo $alertTone; ?> alert-dismissible fade show" role="status">
            <span class="nq-alert-icon"><i class="bi <?php echo $alertIcon; ?>" aria-hidden="true"></i></span>
            <div class="nq-alert-copy">
                <strong>Request status: <?php echo htmlspecialchars($latest_request['status']); ?></strong>
                <p>Your request for <strong><?php echo htmlspecialchars($latest_request['asset_name']); ?></strong> has an update. <?php echo $instruction; ?></p>
                <a href="my_requests.php" class="nq-alert-link">View details <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <section class="nq-toolbar" aria-label="Search and filter inventory">
        <form method="GET" action="" class="nq-search" role="search">
            <i class="bi bi-search nq-search-ico" aria-hidden="true"></i>
            <label for="nqSearchInput" class="visually-hidden">Search equipment</label>
            <input type="text" id="nqSearchInput" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" class="nq-search-input" placeholder="Search equipment...">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
            <button type="submit" class="nq-btn nq-btn--primary">Search</button>
        </form>

        <div class="nq-filter">
            <p class="nq-filter-label" id="nqFilterLabel">Category</p>
            <div class="nq-chips">
                <?php foreach ($categoryLabels as $slug => $label): ?>
                    <?php
                        $chipUrl = '?' . http_build_query(array_merge($queryBase, ['category' => $slug, 'page' => 1]));
                        $isActive = ($categoryFilter === $slug);
                    ?>
                    <a href="<?php echo htmlspecialchars($chipUrl); ?>"
                       class="nq-chip <?php echo $isActive ? 'is-active' : ''; ?>"
                       <?php echo $isActive ? 'aria-current="true"' : ''; ?>>
                        <i class="bi <?php echo $categoryIcons[$slug]; ?>" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="nq-meta">
        <p>
            <?php if ($totalItems > 0): ?>
                Showing <b><?php echo $rangeLabel; ?></b> of <b><?php echo $totalItems; ?></b> <?php echo $totalItems === 1 ? 'item' : 'items'; ?>
                <?php if ($categoryFilter !== 'all'): ?>
                    in <b><?php echo htmlspecialchars($categoryLabels[$categoryFilter] ?? $categoryFilter); ?></b>
                <?php endif; ?>
            <?php else: ?>
                No results
            <?php endif; ?>
        </p>
        <?php if ($isFiltered && $totalItems > 0): ?>
            <a href="inventory_list.php">Clear filters</a>
        <?php endif; ?>
    </div>

    <div class="nq-grid" id="inventoryGrid">
    <?php if (count($assets) > 0): ?>
        <?php foreach ($assets as $index => $row): ?>
            <?php
                $placeholder = "assets/img/no_image_available.jpg";
                $check_path = __DIR__ . "/assets/upload/" . $row['asset_image'];
                $image_path = (!empty($row['asset_image']) && file_exists($check_path)) ? "assets/upload/" . $row['asset_image'] : $placeholder;

                $current_stock = (int) $row['current_stock'];
                $total_stock = $row['total_stock'] ?? null;
                $total_stock = $total_stock !== null ? (int) $total_stock : null;
                $category = $row['category'];
                $low_stock_threshold = 5;

                if ($current_stock <= 0) {
                    $can_borrow = false;
                    $display_status = ($category === 'Consumables' || $category === 'Stationery') ? "Out of Stock" : "Not Available";
                    $status_tone = 'is-out';
                } elseif ($current_stock <= $low_stock_threshold) {
                    $display_status = "Low Stock (" . $current_stock . " left)";
                    $can_borrow = true;
                    $status_tone = 'is-low';
                } else {
                    $display_status = "Available";
                    $can_borrow = true;
                    $status_tone = 'is-ok';
                }

                $stock_percent = null;
                if ($total_stock !== null && $total_stock > 0) {
                    $stock_percent = (int) round(min(100, max(0, ($current_stock / $total_stock) * 100)));
                }
            ?>
            <article class="nq-card asset-item"
                     data-asset-card
                     data-category="<?php echo htmlspecialchars($category, ENT_QUOTES); ?>"
                     data-asset-id="<?php echo (int) $row['asset_id']; ?>"
                     data-asset-name="<?php echo htmlspecialchars($row['asset_name'], ENT_QUOTES); ?>"
                     data-status="<?php echo htmlspecialchars($display_status, ENT_QUOTES); ?>"
                     data-stock="<?php echo $current_stock; ?>"
                     data-total="<?php echo $total_stock !== null ? $total_stock : ''; ?>"
                     data-image="<?php echo htmlspecialchars($image_path, ENT_QUOTES); ?>"
                     style="--d: <?php echo (int) ($index * 45); ?>;">
                <div class="nq-thumb">
                    <img src="<?php echo htmlspecialchars($image_path); ?>"
                         alt=""
                         width="640" height="400"
                         loading="lazy">
                </div>

                <div class="nq-card-body">
                    <p class="nq-card-cat"><?php echo htmlspecialchars($category); ?></p>
                    <h2 class="nq-card-title">
                        <button type="button" class="nq-card-link" data-open-details
                                aria-label="View details for <?php echo htmlspecialchars($row['asset_name'], ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($row['asset_name']); ?>
                        </button>
                    </h2>

                    <p class="nq-status <?php echo $status_tone; ?>">
                        <span class="nq-dot" aria-hidden="true"></span>
                        <?php echo htmlspecialchars($display_status); ?>
                    </p>

                    <div class="nq-stock">
                        <div class="nq-stock-head">
                            <span>Stock</span>
                            <span>
                                <b><?php echo $current_stock; ?></b><?php if ($total_stock !== null): ?><span class="nq-sep"> / </span><?php echo $total_stock; ?><?php endif; ?>
                            </span>
                        </div>
                        <?php if ($stock_percent !== null): ?>
                            <div class="nq-meter <?php echo $status_tone; ?>">
                                <span style="width: <?php echo $stock_percent; ?>%"></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="actions/add_to_cart.php" method="POST" class="nq-card-cta" onsubmit="event.stopPropagation();">
                        <input type="hidden" name="asset_id" value="<?php echo (int) $row['asset_id']; ?>">
                        <button type="submit" class="nq-btn nq-btn--primary" <?php echo !$can_borrow ? 'disabled' : ''; ?> onclick="event.stopPropagation();">
                            <i class="bi <?php echo $can_borrow ? 'bi-bag-plus' : 'bi-slash-circle'; ?>" aria-hidden="true"></i>
                            <span><?php echo $can_borrow ? 'Add to borrowing cart' : htmlspecialchars($display_status); ?></span>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="nq-empty">
            <span class="nq-empty-icon"><i class="bi bi-inboxes" aria-hidden="true"></i></span>
            <h2>No equipment found</h2>
            <p>
                <?php if ($searchTerm !== ''): ?>
                    Nothing matches &ldquo;<?php echo htmlspecialchars($searchTerm); ?>&rdquo;<?php echo $categoryFilter !== 'all' ? ' in ' . htmlspecialchars($categoryLabels[$categoryFilter] ?? $categoryFilter) : ''; ?>.
                <?php else: ?>
                    This category is empty right now.
                <?php endif; ?>
                Try another search or browse every category.
            </p>
            <a href="inventory_list.php" class="nq-btn nq-btn--ghost">Clear filters</a>
        </div>
    <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="nq-pager" aria-label="Inventory pages">
            <a class="nq-page-link nq-page-step <?php echo ($page <= 1) ? 'is-disabled' : ''; ?>"
               href="<?php echo htmlspecialchars($pageUrl(max(1, $page - 1))); ?>"
               <?php echo ($page <= 1) ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                <i class="bi bi-chevron-left" aria-hidden="true"></i><span>Prev</span>
            </a>

            <ul class="nq-page-list">
                <?php foreach ($pageNumbers as $number): ?>
                    <?php if ($number === '...'): ?>
                        <li class="nq-page-gap" aria-hidden="true">&hellip;</li>
                    <?php else: ?>
                        <li>
                            <a class="nq-page-link <?php echo ((int) $number === $page) ? 'is-active' : ''; ?>"
                               href="<?php echo htmlspecialchars($pageUrl((int) $number)); ?>"
                               <?php echo ((int) $number === $page) ? 'aria-current="page"' : ''; ?>>
                                <?php echo (int) $number; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <span class="nq-pager-status">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

            <a class="nq-page-link nq-page-step <?php echo ($page >= $totalPages) ? 'is-disabled' : ''; ?>"
               href="<?php echo htmlspecialchars($pageUrl(min($totalPages, $page + 1))); ?>"
               <?php echo ($page >= $totalPages) ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                <span>Next</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        </nav>
    <?php endif; ?>
</main>

<?php if ($user_role === 'Admin'): ?>
<div class="nq-fab nq-fab--admin">
    <a href="admin/index.php" class="nq-fab-btn" aria-label="Open admin dashboard">
        <i class="bi bi-speedometer2" aria-hidden="true"></i>
        <span class="nq-fab-label">Admin dashboard</span>
    </a>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
<div class="nq-fab nq-fab--cart">
    <a href="cart.php" class="nq-fab-btn" aria-label="View borrowing cart, <?php echo count($_SESSION['cart']); ?> items">
        <i class="bi bi-basket2-fill" aria-hidden="true"></i>
        <span class="nq-fab-label">View cart</span>
        <span class="nq-fab-count"><?php echo count($_SESSION['cart']); ?></span>
    </a>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>

<?php if ($show_status_modal): ?>
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content nq-modal nq-modal--status">
            <div class="modal-body text-center">
                <?php
                    $status = $latest_request['status'];
                    $icon = 'bi-info-circle';
                    $tone = 'is-info';
                    $msg = "Your request is being processed.";
                    if ($status == 'Approved') { $icon = 'bi-check-circle-fill'; $tone = 'is-ok'; $msg = "Your request for <strong>" . htmlspecialchars($latest_request['asset_name']) . "</strong> has been <strong>Approved</strong>! Please head to the ICT Department for collection."; }
                    elseif ($status == 'On Loan') { $icon = 'bi-box-seam-fill'; $tone = 'is-info'; $msg = "You are currently borrowing <strong>" . htmlspecialchars($latest_request['asset_name']) . "</strong>. Don't forget to check the handover notes!"; }
                    elseif ($status == 'Rejected') { $icon = 'bi-exclamation-octagon-fill'; $tone = 'is-danger'; $msg = "Your request for <strong>" . htmlspecialchars($latest_request['asset_name']) . "</strong> was <strong>Rejected</strong>."; }
                    elseif ($status == 'Returned') { $icon = 'bi-arrow-left-right'; $tone = 'is-ok'; $msg = "The item <strong>" . htmlspecialchars($latest_request['asset_name']) . "</strong> has been successfully <strong>Returned</strong>. Thank you!"; }
                ?>
                <span class="nq-status-icon <?php echo $tone; ?>"><i class="bi <?php echo $icon; ?>" aria-hidden="true"></i></span>
                <h2 class="nq-modal-title" id="statusModalTitle">Request update</h2>
                <p class="nq-modal-text"><?php echo $msg; ?></p>
                <div class="d-grid gap-2">
                    <a href="my_requests.php" class="nq-btn nq-btn--primary">View my requests</a>
                    <button type="button" class="nq-btn nq-btn--ghost" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        statusModal.show();
    });
</script>
<?php unset($_SESSION['first_login_session']); endif; ?>

<!-- Asset detail modal -->
<div class="modal fade" id="assetDetailModal" tabindex="-1" aria-labelledby="assetDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content nq-modal">
            <div class="modal-header">
                <div>
                    <p class="nq-modal-eyebrow" id="assetDetailCategory"></p>
                    <h2 class="modal-title nq-modal-title" id="assetDetailModalLabel">Asset details</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="nq-modal-grid">
                    <div class="nq-modal-media">
                        <img id="assetDetailImage" src="" alt="">
                    </div>
                    <div class="nq-modal-info">
                        <div class="nq-modal-statusline">
                            <span class="nq-pill" id="assetDetailStatus">Available</span>
                            <span class="nq-modal-stock" id="assetDetailStock"></span>
                        </div>
                        <div class="nq-meter nq-meter--lg" id="assetDetailMeterWrap">
                            <span id="assetDetailMeter" style="width: 0%;"></span>
                        </div>
                        <p class="nq-modal-note">Stock updates as equipment is borrowed and returned.</p>
                        <div id="assetDetailTags" class="nq-tags">
                            <p class="nq-tags-note">Loading serial details...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="nq-btn nq-btn--ghost" data-bs-dismiss="modal">Close</button>
                <a href="cart.php" class="nq-btn nq-btn--primary">Go to borrowing cart</a>
            </div>
        </div>
    </div>
</div>

<script>
    var NQ_IS_ADMIN = <?php echo $user_role === 'Admin' ? 'true' : 'false'; ?>;

    function openAssetModal(assetId, assetName, category, displayStatus, currentStock, totalStock, imagePath) {
        document.getElementById('assetDetailModalLabel').innerText = assetName;
        document.getElementById('assetDetailCategory').innerText = category;
        document.getElementById('assetDetailImage').src = imagePath;
        document.getElementById('assetDetailImage').alt = assetName;

        var statusEl = document.getElementById('assetDetailStatus');
        statusEl.innerText = displayStatus;
        statusEl.className = 'nq-pill ' + (/out of stock|not available/i.test(displayStatus)
            ? 'is-out'
            : (/low stock/i.test(displayStatus) ? 'is-low' : 'is-ok'));

        document.getElementById('assetDetailStock').innerText = totalStock !== null
            ? 'Stock: ' + currentStock + ' / ' + totalStock
            : 'Stock: ' + currentStock;

        var meterWrap = document.getElementById('assetDetailMeterWrap');
        var meter = document.getElementById('assetDetailMeter');
        if (totalStock !== null && Number(totalStock) > 0) {
            meter.style.width = Math.min(100, Math.round((currentStock / totalStock) * 100)) + '%';
            meterWrap.hidden = false;
        } else {
            meter.style.width = '0%';
            meterWrap.hidden = true;
        }

        var tagsContainer = document.getElementById('assetDetailTags');
        if (!NQ_IS_ADMIN) {
            tagsContainer.innerHTML = '<p class="nq-tags-note">Serial-level details are managed by the ICT Department.</p>';
        } else {
            tagsContainer.innerHTML = '<p class="nq-tags-note">Loading serial details...</p>';
            fetch('admin/actions/get_asset_tags.php?asset_id=' + encodeURIComponent(assetId) + '&show_actions=0')
                .then(function (response) {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(function (html) {
                    tagsContainer.innerHTML = html;
                })
                .catch(function () {
                    tagsContainer.innerHTML = '<p class="nq-tags-note text-danger">Serial details are not available right now.</p>';
                });
        }

        var modal = new bootstrap.Modal(document.getElementById('assetDetailModal'));
        modal.show();
    }

    (function () {
        var grid = document.getElementById('inventoryGrid');

        function openFromCard(card) {
            openAssetModal(
                Number(card.dataset.assetId),
                card.dataset.assetName,
                card.dataset.category,
                card.dataset.status,
                Number(card.dataset.stock),
                card.dataset.total === '' ? null : Number(card.dataset.total),
                card.dataset.image
            );
        }

        if (grid) {
            grid.addEventListener('click', function (event) {
                if (event.target.closest('form, button, a')) return;
                var card = event.target.closest('[data-asset-card]');
                if (card) openFromCard(card);
            });
        }

        document.querySelectorAll('[data-open-details]').forEach(function (button) {
            button.addEventListener('click', function () {
                var card = button.closest('[data-asset-card]');
                if (card) openFromCard(card);
            });
        });
    })();

    // Move the floating actions out of the way when the footer scrolls into view.
    (function () {
        var footer = document.querySelector('footer');
        var fabs = document.querySelectorAll('.nq-fab');
        if (!footer || !fabs.length || !('IntersectionObserver' in window)) return;
        new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                fabs.forEach(function (fab) {
                    fab.classList.toggle('is-hidden', entry.isIntersecting);
                });
            });
        }, { rootMargin: '0px 0px -40px 0px' }).observe(footer);
    })();

    // Original SweetAlert scripts kept exactly the same
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg === 'added') {
        Swal.fire({ icon: 'success', title: 'Added to Cart', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false, timerProgressBar: true });
    } else if (msg === 'submitted') {
        Swal.fire({ icon: 'success', title: 'Request Submitted!', text: 'Your borrowing request has been sent to the ICT Department for approval.', confirmButtonColor: '#00796B', confirmButtonText: 'Great!' });
    }
    if (msg) window.history.replaceState({}, document.title, window.location.pathname);
</script>
</body>
</html>
