<?php
/**
 * Demo mode.
 * ---------------------------------------------------------------------------
 * Enabled only when the DEMO_MODE environment variable is exactly "true".
 * Absent or any other value => the application behaves normally, so the same
 * codebase still runs fully functional on a local XAMPP install.
 *
 * A demo has to show consequences, so the writes that make up the story are
 * *allowed*: adding to the cart, submitting a request, approving or rejecting
 * it, handover, return, adding or editing assets, tags and users, signup.
 *
 * Only the actions that cannot be undone by the next visitor are blocked:
 *
 *   1. deleting an asset, a user or a serial tag;
 *   2. changing, resetting or recovering a password.
 *
 * Those are the ones where a single visitor would break the demo for everyone
 * (a changed admin password locks the account out permanently, a deleted asset
 * leaves a hole the seed data cannot explain). The rest of the demo is live and
 * shared, which is why `demo_reset.php` exists: it restores seed.sql, and the
 * container re-seeds on every start.
 *
 * The demo admin account (`admin`) is additionally protected from edits that
 * would rename it or strip its role — see admin/actions/update_user_full.php.
 */

if (!function_exists('demo_mode_enabled')) {

    /**
     * Scripts that must never run, mapped to the message the visitor sees.
     * Matched by path suffix so it works whether the app is served from the
     * web root or a subdirectory.
     *
     * @return array<string, string>
     */
    function demo_blocked_scripts(): array
    {
        return [
            '/admin/delete_asset.php'              => 'Deleting an asset is disabled in this demo. Everything else works.',
            '/admin/actions/delete_user.php'       => 'Deleting accounts is disabled in this demo. Everything else works.',
            '/admin/actions/delete_asset_tag.php'  => 'Deleting serial tags is disabled in this demo. Everything else works.',
            '/actions/change_password.php'         => 'Password changes are disabled in this demo — sign in with admin / demo1234.',
            '/actions/reset_password.php'          => 'Password resets are disabled in this demo — sign in with admin / demo1234.',
            '/actions/forgot_password.php'         => 'Password recovery is disabled in this demo — sign in with admin / demo1234.',
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
     * The message to show if this request must be blocked, or null if it runs.
     */
    function demo_blocked_reason(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);

        foreach (demo_blocked_scripts() as $suffix => $reason) {
            if (demo_path_ends_with($path, $suffix)) {
                return $reason;
            }
        }

        return null;
    }

    /**
     * Decide whether a request must be blocked. Kept separate from the
     * redirect so it can be unit-tested without a web server.
     *
     * $method is accepted for call-site compatibility and future use; the
     * decision is per script, because a delete reachable by GET is as final
     * as one reached by POST.
     */
    function demo_is_blocked(string $path, string $method = 'GET'): bool
    {
        return demo_blocked_reason($path) !== null;
    }

    /**
     * Block the current request if demo mode is on and the script is one of
     * the irreversible ones. Redirects and exits when blocked.
     */
    function demo_guard(): void
    {
        if (!demo_mode_enabled()) {
            return;
        }

        $path   = $_SERVER['SCRIPT_NAME'] ?? '';
        $reason = demo_blocked_reason($path);

        if ($reason === null) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['demo_notice'] = $reason;
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
     * Print the one-shot notice and clear it.
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
