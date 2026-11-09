<?php
// Securely fetch active page name to toggle active class in sidebar menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* --- Sidebar toggle button --- */
    .btn-menu-toggle {
        color: rgba(255, 255, 255, 0.95);
        border: none;
        transition: all 0.2s ease-in-out;
    }
    .btn-menu-toggle:hover {
        color: #ffffff;
        transform: scale(1.1);
    }
</style>

<?php if (function_exists('demo_render_notice')) { demo_render_notice(); } ?>

<header class="shadow-sm" style="position: sticky; top: 0; z-index: 1000;">
    <nav class="navbar navbar-dark py-3" style="background-color: var(--brand-teal, #00796B); border-bottom: 1px solid rgba(255, 255, 255, 0.15);">
        <div class="container">
            <div class="d-flex align-items-center">
                <!-- Hamburger Menu Toggle Button integrated directly into the navbar -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="btn btn-menu-toggle p-0 me-3 bg-transparent border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#studentSidebar" aria-label="Open Navigation Menu">
                        <i class="bi bi-list fs-3"></i>
                    </button>
                <?php endif; ?>

                <a class="navbar-brand d-flex align-items-center m-0" href="inventory_list.php">
                    <img src="assets/img/logoNuqtah_White.png" alt="ITQSHHB Logo" height="45" style="transition: transform 0.3s ease;">
                </a>
            </div>
            
            <div class="ms-auto d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['full_name'])): ?>
                    <span class="text-white fw-semibold d-none d-md-inline small">
                        <i class="bi bi-person-circle me-1 opacity-75"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </span>
                <?php endif; ?>
                
                <a href="actions/logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3" style="font-weight: 500; transition: all 0.2s ease;">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>
</header>

<?php /* The account drawer lives in includes/users_sidebar.php (single #studentSidebar). */ ?>
