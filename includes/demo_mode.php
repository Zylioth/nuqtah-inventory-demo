<?php
/**
 * Read-only demo mode.
 * ---------------------------------------------------------------------------
 * Enabled only when the DEMO_MODE environment variable is exactly "true".
 * Absent or any other value => the application behaves normally, so the same
 * codebase still runs fully functional on a local XAMPP install.
 *
 * Enforcement
 * -----------
 * Rather than editing ~18 mutating endpoints individually (and risking a
 * missed one on a public URL), the rule is structural:
 *
 *   1. Anything under an `actions/` directory mutates state  -> blocked.
 *   2. A short list of mutating scripts that live outside `actions/` -> blocked.
 *   3. Any POST to a page (cart checkout, add/edit asset, user forms) -> blocked.
 *   4. Login and logout stay allowed: they touch the session, never the database.
 *
 * A new file added under `actions/` is protected automatically. A new mutating
 * *page* outside `actions/` must be added to $BLOCKED_SCRIPTS.
 *
 * Known, deliberate exception: pages such as my_requests.php clear the user's
 * own unread flag on load (UPDATE ... SET is_read = 1). That write still runs,
 * because blocking it would throw on a normal page view. It only affects the
 * notification badge of the account being viewed, never shared inventory data.
 */

if (!function_exists('demo_mode_enabled')) {

    /**
     * Mutating scripts that live outside an actions/ directory.
     * Matched by path suffix so it works whether the app is served from
     * the web root or a subdirectory.
     *
     * @return string[]
     */
    function demo_blocked_scripts(): array
    {
        return [
            '/admin/delete_asset.php',
        ];
    }

    /**
     * Scripts under actions/ that only read. They must stay reachable or the
     * admin forms that populate their dropdowns break (handover / tag picker).
     *
     * @return string[]
     */
    function demo_allowed_scripts(): array
    {
        return [
            '/admin/actions/get_asset_tags.php',
            '/admin/actions/get_available_tags.php',
        ];
    }

    function demo_mode_enabled(): bool
    {
        return getenv('DEMO_MODE') === 'true';
    }

    function demo_path_ends_with(string $path, string $suffix): bool
    {
        return $suffix !== '' && substr($path, -strlen($suffix)) === $suffix;
    }

    /**
     * Decide whether a request must be blocked. Kept separate from the
     * redirect so it can be unit-tested without a web server.
     */
    function demo_is_blocked(string $path, string $method): bool
    {
        $path   = str_replace('\\', '/', $path);
        $method = strtoupper($method);

        // 4. Session-only endpoints stay available so visitors can log in.
        foreach (['/actions/login_process.php', '/actions/logout.php'] as $allowed) {
            if (demo_path_ends_with($path, $allowed)) {
                return false;
            }
        }

        // Read-only helpers under actions/ must keep working.
        foreach (demo_allowed_scripts() as $allowed) {
            if (demo_path_ends_with($path, $allowed)) {
                return false;
            }
        }

        // 1. Any script inside actions/ or admin/actions/.
        if (preg_match('#/(?:admin/)?actions/#', $path) === 1) {
            return true;
        }

        // 2. Mutating scripts outside actions/.
        foreach (demo_blocked_scripts() as $suffix) {
            if (demo_path_ends_with($path, $suffix)) {
                return true;
            }
        }

        // 3. A POST to a page is a form submission (cart, add/edit asset, ...).
        return $method === 'POST';
    }

    /**
     * Block the current request if demo mode is on and it would mutate state.
     * Redirects and exits when blocked; returns otherwise.
     */
    function demo_guard(): void
    {
        if (!demo_mode_enabled()) {
            return;
        }

        $path   = $_SERVER['SCRIPT_NAME'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!demo_is_blocked($path, $method)) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['demo_notice'] = 'This is a read-only demo — changes are disabled.';
        }

        header('Location: ' . demo_return_url(str_replace('\\', '/', $path)));
        exit;
    }

    /**
     * Where to send the visitor after blocking. Prefer the referring page on
     * this host; never bounce back to the blocked script itself.
     */
    function demo_return_url(string $blockedPath): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '') {
            $refHost  = parse_url($referer, PHP_URL_HOST);
            $selfHost = $_SERVER['HTTP_HOST'] ?? '';
            $refPath  = str_replace('\\', '/', (string) parse_url($referer, PHP_URL_PATH));

            if ($refHost && $selfHost && strcasecmp($refHost, $selfHost) === 0
                && $refPath !== $blockedPath) {
                return $referer;
            }
        }

        // Fallback: one level above the actions/ directory, else the app root.
        $dir = rtrim(str_replace('\\', '/', dirname($blockedPath)), '/');
        $dir = preg_replace('#/actions$#', '', $dir);
        return $dir !== '' ? $dir . '/index.php' : '/index.php';
    }

    /**
     * Print the one-shot "read-only" notice and clear it.
     * Called from both layouts (user header and admin sidebar).
     */
    function demo_render_notice(): void
    {
        if (!demo_mode_enabled() || empty($_SESSION['demo_notice'])) {
            return;
        }

        $message = (string) $_SESSION['demo_notice'];
        unset($_SESSION['demo_notice']);
        ?>
        <style>
            #demoToast {
                position: fixed;
                top: 16px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 2147483000;
                max-width: min(92vw, 480px);
                padding: 12px 18px;
                border-radius: 12px;
                background: #263238;
                color: #ffffff;
                font: 500 14px/1.45 'Outfit', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
                display: flex;
                align-items: center;
                gap: 10px;
            }
            #demoToast::before { content: '🔒'; font-size: 16px; line-height: 1; }
        </style>
        <div id="demoToast" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php
    }
}
