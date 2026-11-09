<?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $user_name    = $_SESSION['full_name'] ?? 'Student';
    $user_role    = $_SESSION['role'] ?? 'Staff';
    $initial      = function_exists('mb_substr') ? mb_substr($user_name, 0, 1) : substr($user_name, 0, 1);
    $cart_count   = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

    $menu_links = [
        ['inventory_list.php', 'bi-grid-1x2-fill', 'Browse inventory'],
        ['my_requests.php', 'bi-clock-history', 'My requests'],
        ['change_password.php', 'bi-key-fill', 'Change password'],
    ];
?>
<style>
    :root {
        --us-teal: #00796B;
        --us-deep: #004D40;
        --us-ink: #0B2B26;
        --us-body: rgba(0, 77, 64, 0.72);
        --us-line: rgba(0, 77, 64, 0.1);
        --us-font: 'Outfit', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    }

    .us-drawer {
        --bs-offcanvas-width: 300px;
        width: 300px;
        max-width: 88vw;
        display: flex;
        flex-direction: column;
        border: 0;
        background: #fff;
        font-family: var(--us-font);
        box-shadow: 18px 0 45px rgba(0, 30, 25, 0.16);
    }

    .us-head {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.1rem;
        background: var(--us-teal);
    }
    .us-brand { display: inline-flex; align-items: center; text-decoration: none; }
    .us-brand img { height: 34px; width: auto; display: block; }
    .us-close {
        display: grid;
        place-items: center;
        width: 2.1rem;
        height: 2.1rem;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.8rem;
        line-height: 1;
        transition: background-color 0.2s ease;
    }
    .us-close:hover { background: rgba(255, 255, 255, 0.24); color: #fff; }

    .us-user {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid var(--us-line);
    }
    .us-avatar {
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 50%;
        background: rgba(0, 121, 107, 0.1);
        color: var(--us-teal);
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .us-user-text { min-width: 0; }
    .us-user-text strong {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.3;
        color: var(--us-ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .us-user-text span {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.1rem;
        font-size: 0.72rem;
        color: var(--us-body);
    }
    .us-user-text span::before {
        content: "";
        width: 0.35rem;
        height: 0.35rem;
        border-radius: 50%;
        background: #2E9E5B;
    }

    .us-nav {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding: 0.85rem 0.7rem 1rem;
        overscroll-behavior: contain;
    }
    .us-label {
        margin: 0.15rem 0 0.4rem;
        padding: 0 0.55rem;
        font-size: 0.64rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgba(0, 77, 64, 0.45);
    }
    .us-link {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.6rem 0.7rem;
        margin-bottom: 0.15rem;
        border-radius: 10px;
        color: #435a55;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.18s ease, color 0.18s ease;
    }
    .us-link i { flex: 0 0 auto; font-size: 1rem; opacity: 0.8; }
    .us-link span { min-width: 0; }
    .us-link:hover { background: #f2f8f6; color: var(--us-teal); }
    .us-link:hover i { opacity: 1; }
    .us-link.is-active {
        background: var(--us-teal);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 6px 14px -8px rgba(0, 121, 107, 0.9);
    }
    .us-link.is-active i { opacity: 1; }
    .us-link.is-active:hover { background: var(--us-teal); color: #fff; }
    .us-count {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.4rem;
        height: 1.4rem;
        padding: 0 0.35rem;
        border-radius: 999px;
        background: var(--us-teal);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .us-link.is-active .us-count { background: rgba(255, 255, 255, 0.24); }

    .us-foot {
        flex: 0 0 auto;
        padding: 0.7rem;
        border-top: 1px solid var(--us-line);
    }
    .us-link--danger { color: #B3261E; }
    .us-link--danger:hover { background: #FCF3F2; color: #B3261E; }
    .us-note {
        margin: 0.35rem 0 0;
        padding: 0 0.7rem;
        font-size: 0.7rem;
        color: rgba(0, 77, 64, 0.45);
    }

    .us-drawer a:focus-visible,
    .us-drawer button:focus-visible {
        outline: 2px solid #fff;
        outline-offset: -2px;
    }

    @media (prefers-reduced-motion: reduce) {
        .us-close, .us-link { transition: none; }
    }
</style>

<aside class="offcanvas offcanvas-start us-drawer" tabindex="-1" id="studentSidebar" aria-label="Account menu">
    <div class="us-head">
        <a href="inventory_list.php" class="us-brand">
            <img src="assets/img/logoNuqtah_White.png" alt="Nuqtah">
        </a>
        <button type="button" class="us-close" data-bs-dismiss="offcanvas" aria-label="Close menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="us-user">
        <span class="us-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></span>
        <div class="us-user-text">
            <strong><?php echo htmlspecialchars($user_name); ?></strong>
            <span><?php echo htmlspecialchars($user_role); ?></span>
        </div>
    </div>

    <nav class="us-nav" aria-label="Menu">
        <p class="us-label">Menu</p>
        <?php foreach ($menu_links as [$href, $icon, $label]): ?>
            <?php $is_active = ($current_page === $href); ?>
            <a href="<?php echo $href; ?>"
               class="us-link <?php echo $is_active ? 'is-active' : ''; ?>"
               <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                <i class="bi <?php echo $icon; ?>" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($label); ?></span>
            </a>
        <?php endforeach; ?>

        <?php if ($cart_count > 0): ?>
            <p class="us-label mt-3">Borrowing</p>
            <a href="cart.php" class="us-link <?php echo ($current_page === 'cart.php') ? 'is-active' : ''; ?>">
                <i class="bi bi-basket2-fill" aria-hidden="true"></i>
                <span>Borrowing cart</span>
                <span class="us-count" aria-label="<?php echo $cart_count; ?> items in cart"><?php echo $cart_count; ?></span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="us-foot">
        <a href="actions/logout.php" class="us-link us-link--danger">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span>Sign out</span>
        </a>
        <p class="us-note">Nuqtah @ ITQSHHB</p>
    </div>
</aside>
