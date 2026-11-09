<?php
/**
 * Dependency-free checks for the read-only demo guard.
 *
 *   php tests/demo_guard_test.php
 *
 * Covers every mutating endpoint in the app, so a refactor that reopens a
 * write path fails here instead of on the public demo.
 */

require_once __DIR__ . '/../includes/demo_mode.php';

$cases = [
    // --- allowed: session-only, no database write ---------------------------
    ['GET',  '/actions/login_process.php',          false, 'login stays possible'],
    ['POST', '/actions/login_process.php',          false, 'login posts stay possible'],
    ['GET',  '/actions/logout.php',                 false, 'logout stays possible'],

    // --- allowed: ordinary read-only page views -----------------------------
    ['GET',  '/index.php',                          false, 'landing page'],
    ['GET',  '/inventory_list.php',                 false, 'inventory browsing'],
    ['GET',  '/cart.php',                           false, 'cart page loads'],
    ['GET',  '/my_requests.php',                    false, 'own requests load'],
    ['GET',  '/admin/index.php',                    false, 'admin dashboard'],
    ['GET',  '/admin/manage_assets.php',            false, 'admin inventory'],
    ['GET',  '/admin/view_requests.php',            false, 'admin requests'],
    ['GET',  '/admin/report.php',                   false, 'reports'],
    ['GET',  '/admin/admin_logs.php',               false, 'activity log'],
    ['GET',  '/admin/actions/get_asset_tags.php',   false, 'read-only helper, allowed despite actions/'],
    ['GET',  '/admin/actions/get_available_tags.php', false, 'read-only helper, allowed despite actions/'],

    // --- blocked: every mutating endpoint in the app ------------------------
    ['POST', '/actions/signup_process.php',         true,  'signup'],
    ['POST', '/actions/submit_borrow.php',          true,  'submit borrowing request'],
    ['POST', '/actions/add_to_cart.php',            true,  'add to cart'],
    ['POST', '/actions/update_cart.php',            true,  'update cart'],
    ['POST', '/actions/remove_from_cart.php',       true,  'remove from cart'],
    ['POST', '/actions/change_password.php',        true,  'change password'],
    ['POST', '/actions/forgot_password.php',        true,  'forgot password'],
    ['POST', '/actions/reset_password.php',         true,  'reset password'],
    ['GET',  '/actions/activate.php',               true,  'account activation'],
    ['GET',  '/admin/actions/process_request.php',  true,  'approve/reject uses $_REQUEST, so GET mutates'],
    ['GET',  '/admin/actions/process_handover.php', true,  'handover'],
    ['GET',  '/admin/actions/process_return.php',   true,  'return'],
    ['POST', '/admin/actions/add_users.php',        true,  'add user'],
    ['POST', '/admin/actions/update_user_full.php', true,  'edit user'],
    ['GET',  '/admin/actions/delete_user.php',      true,  'delete user'],
    ['POST', '/admin/actions/add_asset_tag.php',    true,  'add serial tag'],
    ['POST', '/admin/actions/delete_asset_tag.php', true,  'delete serial tag'],
    ['POST', '/admin/actions/update_tag_status.php', true, 'tag status / maintenance'],
    ['GET',  '/admin/delete_asset.php',             true,  'delete asset lives outside actions/'],

    // --- blocked: form posts to pages ---------------------------------------
    ['POST', '/admin/add_assets.php',               true,  'add asset form'],
    ['POST', '/admin/edit_asset.php',               true,  'edit asset form'],
    ['POST', '/signup.php',                         true,  'signup form'],
    ['POST', '/cart.php',                           true,  'cart checkout'],

    // --- path forms ---------------------------------------------------------
    ['GET',  '/Nuqtah_IT/admin/actions/process_request.php', true, 'served from a subdirectory'],
    ['GET',  '/Nuqtah_IT/inventory_list.php',       false, 'subdirectory read page'],
    ['GET',  '\\Nuqtah_IT\\admin\\actions\\process_return.php', true, 'backslash path from Windows'],
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

printf("\n%d cases, %d failed\n", count($cases), $failures);
exit($failures === 0 ? 0 : 1);
