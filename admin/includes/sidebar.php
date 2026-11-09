<?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $admin_name = $_SESSION['full_name'] ?? 'Administrator';
    $logo_path = '../assets/img/logoNuqtah.png';
    $nav_groups = [
        'Overview' => [
            ['index.php', 'bi-grid-1x2-fill', 'Dashboard'],
        ],
        'Operations' => [
            ['manage_assets.php', 'bi-archive', 'Inventory'],
            ['view_requests.php', 'bi-clipboard-check', 'Requests'],
            ['manage_users.php', 'bi-people', 'Users'],
        ],
        'Records' => [
            ['report.php', 'bi-bar-chart-line-fill', 'Reports'],
            ['admin_logs.php', 'bi-clock-history', 'Activity logs'],
        ],
    ];
?>
<?php if (function_exists('demo_render_notice')) { demo_render_notice(); } ?>
<style>
    :root {
        --sidebar-width: 260px;
        --nuqtah-teal: #00796B;
        --nuqtah-teal-hover: #004D40;
    }

    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        height: 100dvh;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1050;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-right: 1px solid rgba(0, 77, 64, 0.1);
        font-family: 'Outfit', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), visibility 0.28s;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 1.15rem 1.15rem 1rem;
        text-decoration: none;
    }
    .sidebar-brand img { max-width: 158px; height: auto; display: block; }
    .sidebar-brand-text { font-size: 1.15rem; font-weight: 700; letter-spacing: -0.01em; color: var(--nuqtah-teal); }

    .sidebar-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding: 0 0.6rem 0.75rem;
        overscroll-behavior: contain;
    }
    .sidebar-scroll::-webkit-scrollbar { width: 6px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0, 77, 64, 0.16); border-radius: 999px; }

    .sidebar-label {
        margin: 1.1rem 0 0.4rem;
        padding: 0 0.65rem;
        font-size: 0.64rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgba(0, 77, 64, 0.5);
    }

    .sidebar .nav-link {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.6rem 0.7rem;
        margin-bottom: 0.15rem;
        border-radius: 10px;
        color: #435a55 !important;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none !important;
        transition: background-color 0.18s ease, color 0.18s ease;
    }
    .sidebar .nav-link i { font-size: 1rem; opacity: 0.85; }
    .sidebar .nav-link:hover { background-color: #f0f7f6 !important; color: var(--nuqtah-teal) !important; }
    .sidebar .nav-link:hover i { opacity: 1; }
    .sidebar .nav-link.active {
        background-color: var(--nuqtah-teal) !important;
        color: #ffffff !important;
        font-weight: 600;
        box-shadow: 0 6px 14px -8px rgba(0, 121, 107, 0.8);
    }
    .sidebar .nav-link.active i { opacity: 1; }

    .sidebar-foot {
        flex: 0 0 auto;
        padding: 0.75rem 0.6rem 1rem;
        border-top: 1px solid rgba(0, 77, 64, 0.08);
    }
    .sidebar-user {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.15rem 0.65rem 0.6rem;
    }
    .sidebar-user-icon {
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background: rgba(0, 121, 107, 0.1);
        color: var(--nuqtah-teal);
        font-size: 0.9rem;
    }
    .sidebar-user strong { display: block; font-size: 0.82rem; font-weight: 600; color: #0B2B26; line-height: 1.25; }
    .sidebar-user span { display: block; font-size: 0.72rem; color: rgba(0, 77, 64, 0.6); }
    .sidebar .nav-link.text-danger { color: #B3261E !important; }
    .sidebar .nav-link.text-danger:hover { background-color: #FCF3F2 !important; color: #B3261E !important; }

    .sidebar-close {
        margin-left: auto;
        display: none;
        place-items: center;
        width: 2rem;
        height: 2rem;
        padding: 0;
        border: 1px solid rgba(0, 77, 64, 0.14);
        border-radius: 9px;
        background: #fff;
        color: #0B2B26;
        font-size: 0.85rem;
        line-height: 1;
    }
    .sidebar-close:hover { background: #f0f7f6; }

    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1040;
        background: rgba(0, 33, 27, 0.45);
        backdrop-filter: blur(1px);
    }
    .sidebar-overlay.active { display: block; }

    body.adm-nav-open { overflow: hidden; }

    @media (max-width: 991.98px) {
        .sidebar { transform: translateX(-100%); visibility: hidden; box-shadow: none; }
        .sidebar.active { transform: translateX(0); visibility: visible; box-shadow: 18px 0 45px rgba(0, 30, 25, 0.18); }
        .sidebar-close { display: grid; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sidebar { transition: none; }
    }

    @media print {
        .sidebar,
        .sidebar-overlay,
        .sidebar-fallback-toggle { display: none !important; }
    }
</style>

<aside class="sidebar adm-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="sidebar-brand">
        <a href="index.php" class="d-flex align-items-center text-decoration-none">
            <?php if (file_exists($logo_path)): ?>
                <img src="<?php echo $logo_path; ?>" alt="Nuqtah">
            <?php else: ?>
                <span class="sidebar-brand-text">NUQTAH</span>
            <?php endif; ?>
        </a>
        <button type="button" class="sidebar-close" data-sidebar-close aria-label="Close navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="sidebar-scroll">
        <?php foreach ($nav_groups as $group_label => $links): ?>
            <p class="sidebar-label"><?php echo htmlspecialchars($group_label); ?></p>
            <ul class="nav nav-pills flex-column">
                <?php foreach ($links as [$href, $icon, $label]): ?>
                    <li class="nav-item">
                        <a href="<?php echo $href; ?>" class="nav-link <?php echo ($current_page == $href) ? 'active' : ''; ?>"
                           <?php echo ($current_page == $href) ? 'aria-current="page"' : ''; ?>>
                            <i class="bi <?php echo $icon; ?>" aria-hidden="true"></i><span><?php echo htmlspecialchars($label); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>

    <div class="sidebar-foot">
        <div class="sidebar-user">
            <span class="sidebar-user-icon"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
            <div>
                <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                <span>Administrator</span>
            </div>
        </div>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="../inventory_list.php" class="nav-link">
                    <i class="bi bi-shop" aria-hidden="true"></i><span>User view</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../actions/logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Sign out</span>
                </a>
            </li>
        </ul>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" hidden></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('adminSidebar');
        var overlay = document.getElementById('sidebarOverlay');
        if (!sidebar || !overlay) return;

        var isOpen = false;

        function setOpen(open) {
            isOpen = open;
            sidebar.classList.toggle('active', open);
            overlay.classList.toggle('active', open);
            overlay.hidden = !open;
            document.body.classList.toggle('adm-nav-open', open);
            document.querySelectorAll('[data-sidebar-toggle], #menuToggle').forEach(function (button) {
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        function toggle() { setOpen(!isOpen); }

        document.querySelectorAll('[data-sidebar-toggle], #menuToggle').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                toggle();
            });
        });

        sidebar.querySelectorAll('[data-sidebar-close]').forEach(function (button) {
            button.addEventListener('click', function () { setOpen(false); });
        });

        overlay.addEventListener('click', function () { setOpen(false); });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen) setOpen(false);
        });

        // Close after tapping a link on a small screen.
        sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 991.98px)').matches) setOpen(false);
            });
        });

        // Pages without their own hamburger still get a way to open the menu.
        if (!document.querySelector('[data-sidebar-toggle], #menuToggle')) {
            var fab = document.createElement('button');
            fab.type = 'button';
            fab.className = 'sidebar-fallback-toggle';
            fab.setAttribute('data-sidebar-toggle', '');
            fab.setAttribute('aria-label', 'Open navigation');
            fab.setAttribute('aria-expanded', 'false');
            fab.innerHTML = '<i class="bi bi-list" aria-hidden="true"></i>';
            fab.style.cssText = 'position:fixed;right:1rem;bottom:1rem;z-index:1035;display:none;place-items:center;width:3rem;height:3rem;border:0;border-radius:50%;background:#00796B;color:#fff;font-size:1.35rem;box-shadow:0 14px 26px -12px rgba(0,60,50,.8)';
            document.body.appendChild(fab);
            fab.addEventListener('click', toggle);

            var mq = window.matchMedia('(max-width: 991.98px)');
            var sync = function () { fab.style.display = mq.matches ? 'grid' : 'none'; };
            if (mq.addEventListener) { mq.addEventListener('change', sync); } else { mq.addListener(sync); }
            sync();
        }
    });
</script>
