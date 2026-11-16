<?php
/**
 * End-to-end check of the demo's flows against a running server.
 *
 *   php tests/demo_flow_test.php http://127.0.0.1:8131
 *
 * Requires: the app served with DEMO_MODE=true against a freshly seeded
 * database (run `php demo_reset.php` first). It drives the real HTTP flow —
 * login, add to cart, checkout, admin approval — then proves that a delete is
 * still refused, and finishes by resetting the data.
 *
 * It writes to the database on purpose; that is the point. Run it only against
 * the demo database.
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8131', '/');

require_once __DIR__ . '/../includes/db_connect.php'; // $pdo, for verification

$jar = tempnam(sys_get_temp_dir(), 'nuqtah-demo-');
$failures = 0;

function http(string $method, string $path, ?array $data = null): array
{
    global $base, $jar;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_TIMEOUT        => 20,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data ?? []));
    }
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $size = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return ['code' => $code, 'head' => substr((string) $raw, 0, $size), 'body' => substr((string) $raw, $size)];
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $failures;
    if (!$ok) {
        $failures++;
    }
    printf("%-46s %s%s\n", $label, $ok ? 'PASS' : 'FAIL', $detail !== '' ? '  ' . $detail : '');
}

function count_requests(PDO $pdo, string $where = ''): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM borrowing_requests ' . $where)->fetchColumn();
}

$startCount = count_requests($pdo, "WHERE status = 'Pending'");

// --- user side --------------------------------------------------------------
$r = http('POST', '/actions/login_process.php', ['username' => 'admin', 'password' => 'demo1234']);
check('login as admin', $r['code'] === 302);

$asset = $pdo->query("SELECT asset_id, asset_name, current_stock FROM assets
                      WHERE LOWER(category) = 'consumables' AND current_stock > 0
                      ORDER BY asset_id LIMIT 1")->fetch();
check('a consumable asset is in stock', (bool) $asset, $asset ? $asset['asset_name'] : 'none found');

$r = http('POST', '/actions/add_to_cart.php', ['asset_id' => $asset['asset_id']]);
check('add to cart is allowed', $r['code'] === 302, 'HTTP ' . $r['code']);

$r = http('GET', '/cart.php');
check('cart shows the item', strpos($r['body'], (string) $asset['asset_name']) !== false);

$r = http('POST', '/cart.php', [
    'pickup_date' => date('Y-m-d'),
    'return_date' => date('Y-m-d', strtotime('+2 days')),
]);
check('checkout submits', $r['code'] === 302, 'HTTP ' . $r['code']);

$newPending = count_requests($pdo, "WHERE status = 'Pending'");
check('a request row was written', $newPending === $startCount + 1, "pending {$startCount} -> {$newPending}");

$newId = (int) $pdo->query("SELECT MAX(request_id) FROM borrowing_requests")->fetchColumn();

$r = http('GET', '/my_requests.php');
check('the request appears in My Requests', strpos($r['body'], (string) $asset['asset_name']) !== false);

// --- admin side -------------------------------------------------------------
$r = http('GET', '/admin/actions/process_request.php?id=' . $newId . '&action=approve');
check('approve is allowed', $r['code'] === 302, 'HTTP ' . $r['code']);

$status = $pdo->query('SELECT status FROM borrowing_requests WHERE request_id = ' . $newId)->fetchColumn();
check('request moved to Approved', $status === 'Approved', 'status=' . $status);

// --- admin: create an asset, hand one over, take it back --------------------
$assetCount = (int) $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
$r = http('POST', '/admin/add_assets.php', [
    'asset_name'    => 'Flow Test Projector',
    'category'      => 'Equipment',
    'current_stock' => 1,
]);
check('adding an asset is allowed', in_array($r['code'], [200, 302], true), 'HTTP ' . $r['code']);
$assetCountAfter = (int) $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
check('the asset was created', $assetCountAfter === $assetCount + 1, "{$assetCount} -> {$assetCountAfter}");

$target = $pdo->query("SELECT br.request_id, br.asset_id
                       FROM borrowing_requests br
                       JOIN assets a ON a.asset_id = br.asset_id
                       WHERE br.status = 'Approved' AND LOWER(a.category) <> 'consumables'
                       ORDER BY br.request_id LIMIT 1")->fetch();
check('an approved durable request exists', (bool) $target,
    $target ? 'request ' . $target['request_id'] : 'none seeded');

if ($target) {
    $tag = $pdo->query('SELECT tag_id FROM asset_tags WHERE asset_id = ' . (int) $target['asset_id']
        . " AND status = 'Available' LIMIT 1")->fetchColumn();
    if ($tag === false) {
        $tag = $pdo->query('SELECT tag_id FROM asset_tags WHERE asset_id = ' . (int) $target['asset_id']
            . ' LIMIT 1')->fetchColumn();
    }

    $r = http('POST', '/admin/actions/process_handover.php', [
        'request_id'    => $target['request_id'],
        'assigned_tag'  => $tag,
        'handover_note' => 'Flow test handover',
    ]);
    check('handover is allowed', $r['code'] === 302, 'HTTP ' . $r['code']);

    $status = $pdo->query('SELECT status FROM borrowing_requests WHERE request_id = ' . (int) $target['request_id'])->fetchColumn();
    check('request left the Approved queue', in_array($status, ['Issued', 'On Loan'], true), 'status=' . $status);

    $r = http('POST', '/admin/actions/process_return.php', [
        'request_id'    => $target['request_id'],
        'return_status' => 'Available',
        'return_note'   => 'Flow test return',
    ]);
    check('return is allowed', $r['code'] === 302, 'HTTP ' . $r['code']);

    $status = $pdo->query('SELECT status FROM borrowing_requests WHERE request_id = ' . (int) $target['request_id'])->fetchColumn();
    check('request marked Returned', $status === 'Returned', 'status=' . $status);
}

// --- still refused ----------------------------------------------------------
$assetsBefore = (int) $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
$r = http('GET', '/admin/delete_asset.php?id=' . $asset['asset_id']);
check('delete asset is refused', $r['code'] === 302, 'HTTP ' . $r['code']);

$assetsAfter = (int) $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
check('the asset still exists', $assetsAfter === $assetsBefore, "{$assetsBefore} -> {$assetsAfter}");

$location = '';
if (preg_match('/^Location:\s*(.+)$/mi', $r['head'], $m)) {
    $location = trim($m[1]);
}
$r2 = http('GET', parse_url($location, PHP_URL_PATH) ?: '/admin/manage_assets.php');
check('the refusal explains itself', strpos($r2['body'], 'Deleting an asset is disabled') !== false);

// --- reset ------------------------------------------------------------------
$r = http('GET', '/admin/index.php');
preg_match('/name="csrf" value="([^"]+)"/', $r['body'], $m);
$token = $m[1] ?? '';
check('reset button is rendered for admins', $token !== '');

$r = http('POST', '/demo_reset.php', ['csrf' => $token]);
check('reset runs', $r['code'] === 302, 'HTTP ' . $r['code']);

$pendingAfterReset = count_requests($pdo, "WHERE status = 'Pending'");
$users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
check('data is back to the seed', $pendingAfterReset === $startCount && $users === 7,
    "pending={$pendingAfterReset} users={$users}");

@unlink($jar);
printf("\n%s\n", $failures === 0 ? 'ALL PASS' : $failures . ' FAILED');
exit($failures === 0 ? 0 : 1);
