<?php
/**
 * Dependency-free checks for the demo guard.
 *
 *   php tests/demo_guard_test.php
 *
 * The demo is writable on purpose: the flows have to show consequences. These
 * cases pin down the boundary — the only scripts that may be blocked are the
 * irreversible ones (deletes, password changes). If a future edit starts
 * blocking a flow, or stops blocking a delete, this fails.
 */

require_once __DIR__ . '/../includes/demo_mode.php';

$cases = [
    // --- allowed: the flows the demo exists to show -------------------------
    ['GET',  '/actions/login_process.php',           false, 'login'],
    ['POST', '/actions/login_process.php',           false, 'login posts'],
    ['GET',  '/actions/logout.php',                  false, 'logout'],
    ['POST', '/actions/signup_process.php',          false, 'signup creates a pending account'],
    ['POST', '/actions/add_to_cart.php',             false, 'add to cart'],
    ['POST', '/actions/update_cart.php',             false, 'update cart'],
    ['POST', '/actions/remove_from_cart.php',        false, 'remove from cart'],
    ['GET',  '/actions/activate.php',                false, 'account activation'],
    ['POST', '/cart.php',                            false, 'cart checkout'],
    ['POST', '/signup.php',                          false, 'signup form'],
    ['POST', '/admin/add_assets.php',                false, 'add asset form'],
    ['POST', '/admin/edit_asset.php',                false, 'edit asset form'],
    ['GET',  '/admin/actions/process_request.php',   false, 'approve / reject'],
    ['POST', '/admin/actions/process_handover.php',  false, 'handover'],
    ['POST', '/admin/actions/process_return.php',    false, 'return'],
    ['POST', '/admin/actions/add_users.php',         false, 'add user'],
    ['POST', '/admin/actions/update_user_full.php',  false, 'edit user'],
    ['POST', '/admin/actions/add_asset_tag.php',     false, 'add serial tag'],
    ['POST', '/admin/actions/update_tag_status.php', false, 'tag status / maintenance'],
    ['POST', '/demo_reset.php',                      false, 'reset demo data'],

    // --- allowed: ordinary page views ---------------------------------------
    ['GET',  '/index.php',                           false, 'landing page'],
    ['GET',  '/inventory_list.php',                  false, 'inventory browsing'],
    ['GET',  '/cart.php',                            false, 'cart page loads'],
    ['GET',  '/my_requests.php',                     false, 'own requests load'],
    ['GET',  '/admin/index.php',                     false, 'admin dashboard'],
    ['GET',  '/admin/manage_assets.php',             false, 'admin inventory'],
    ['GET',  '/admin/view_requests.php',             false, 'admin requests'],
    ['GET',  '/admin/report.php',                    false, 'reports'],
    ['GET',  '/admin/admin_logs.php',                false, 'activity log'],
    ['GET',  '/admin/actions/get_asset_tags.php',    false, 'read-only helper'],
    ['GET',  '/admin/actions/get_available_tags.php', false, 'read-only helper'],

    // --- blocked: irreversible, or would break the shared demo --------------
    ['GET',  '/admin/delete_asset.php',              true,  'delete asset'],
    ['GET',  '/admin/actions/delete_user.php',       true,  'delete user'],
    ['GET',  '/admin/actions/delete_asset_tag.php',  true,  'delete serial tag'],
    ['POST', '/actions/change_password.php',         true,  'change password'],
    ['POST', '/actions/forgot_password.php',         true,  'forgot password'],
    ['POST', '/actions/reset_password.php',          true,  'reset password'],

    // --- path forms ---------------------------------------------------------
    ['GET',  '/Nuqtah_IT/admin/delete_asset.php',    true,  'served from a subdirectory'],
    ['GET',  '/Nuqtah_IT/inventory_list.php',        false, 'subdirectory read page'],
    ['GET',  '\\Nuqtah_IT\\admin\\actions\\delete_user.php', true, 'backslash path from Windows'],
];

$failures = 0;
foreach ($cases as [$method, $path, $expected, $label]) {
    $actual = demo_is_blocked($path, $method);
    if ($actual !== $expected) {
        $failures++;
        printf("FAIL  %-8s %-52s expected %s, got %s  (%s)\n",
            $method, $path, var_export($expected, true), var_export($actual, true), $label);
    }
}

// Every blocked script must carry a message a visitor can act on.
foreach (array_keys(demo_blocked_scripts()) as $blocked) {
    $reason = demo_blocked_reason($blocked);
    if ($reason === null || strlen($reason) < 20) {
        $failures++;
        printf("FAIL  blocked script %s has no useful message\n", $blocked);
    }
}

printf("\n%d cases, %d failed\n", count($cases), $failures);
exit($failures === 0 ? 0 : 1);
