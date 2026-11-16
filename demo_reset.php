<?php
/**
 * Restore the demo database to seed.sql.
 * ---------------------------------------------------------------------------
 * The demo is writable, so its data drifts: visitors add requests, approve
 * them, add assets. This puts the seeded state back.
 *
 *   CLI   php demo_reset.php          (used by docker/entrypoint.sh on boot)
 *   Web   POST from the admin sidebar "Reset demo data" button, Admin only.
 *
 * Both paths need DEMO_MODE=true: without it this is a normal application and
 * wiping its tables is exactly the accident we are trying to avoid.
 */

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    session_start();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Location: admin/index.php');
        exit;
    }

    if (($_SESSION['role'] ?? '') !== 'Admin') {
        header('Location: index.php');
        exit;
    }

    $token = (string) ($_POST['csrf'] ?? '');
    if (empty($_SESSION['demo_reset_csrf']) || !hash_equals($_SESSION['demo_reset_csrf'], $token)) {
        $_SESSION['demo_notice'] = 'That reset request expired — try again.';
        header('Location: admin/index.php');
        exit;
    }
}

// db_connect.php loads .env first, so DEMO_MODE is known only after it runs.
try {
    require_once __DIR__ . '/includes/db_connect.php'; // opens $pdo, runs the guard
} catch (Throwable $e) {
    $message = 'Cannot reach the database: ' . $e->getMessage();
    if ($isCli) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
    http_response_code(500);
    exit('Reset failed — ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
}

if (getenv('DEMO_MODE') !== 'true') {
    $message = 'Refusing to reset: DEMO_MODE is not "true" (this looks like the real application).';
    if ($isCli) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
    http_response_code(403);
    exit($message);
}

/**
 * Add columns the application writes but an older database may not have.
 *
 * `cart.php` has always inserted `pickup_date` and the admin request list has
 * always displayed it, but the column was never added to schema.sql, so
 * checkout failed on any database built from this repository. Creating it here
 * means an existing Railway database repairs itself on the next start instead
 * of needing a manual ALTER through the public proxy.
 */
function demo_ensure_schema(PDO $pdo): array
{
    $required = [
        'borrowing_requests' => [
            'pickup_date' => 'ADD COLUMN `pickup_date` date DEFAULT NULL AFTER `return_date`',
        ],
    ];

    $added = [];
    foreach ($required as $table => $columns) {
        $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS
                               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        $existing = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');

        foreach ($columns as $column => $ddl) {
            if (!in_array($column, $existing, true)) {
                $pdo->exec('ALTER TABLE `' . $table . '` ' . $ddl);
                $added[] = $table . '.' . $column;
            }
        }
    }

    return $added;
}

/**
 * Run seed.sql one statement at a time.
 *
 * PDO::exec() on a whole file is engine-dependent: some MySQL drivers run only
 * the first statement, which would truncate the tables and insert nothing. The
 * split is by ";" at end of line, after dropping comment lines, which matches
 * the shape of this file (no semicolons inside string literals).
 */
function demo_reset_apply(PDO $pdo, string $seedPath): array
{
    $raw = file_get_contents($seedPath);
    if ($raw === false) {
        throw new RuntimeException('Cannot read ' . $seedPath);
    }

    $kept = [];
    foreach (preg_split('/\R/', $raw) as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || strpos($trimmed, '--') === 0) {
            continue;
        }
        $kept[] = rtrim($line);
    }

    $statements = preg_split('/;\s*(?:\R|$)/', implode("\n", $kept));
    $ran = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
        $ran++;
    }

    return [
        'statements' => $ran,
        'counts'     => [
            'users'              => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'assets'             => (int) $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn(),
            'asset_tags'         => (int) $pdo->query('SELECT COUNT(*) FROM asset_tags')->fetchColumn(),
            'borrowing_requests' => (int) $pdo->query('SELECT COUNT(*) FROM borrowing_requests')->fetchColumn(),
            'admin_logs'         => (int) $pdo->query('SELECT COUNT(*) FROM admin_logs')->fetchColumn(),
        ],
    ];
}

try {
    $added  = demo_ensure_schema($pdo);
    $result = demo_reset_apply($pdo, __DIR__ . '/seed.sql');
    $result['added'] = $added;
} catch (Throwable $e) {
    $message = 'Reset failed: ' . $e->getMessage();
    if ($isCli) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
    http_response_code(500);
    exit(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
}

if ($isCli) {
    echo 'demo_reset: ' . $result['statements'] . " statements\n";
    if (!empty($result['added'])) {
        echo '  schema repaired: ' . implode(', ', $result['added']) . "\n";
    }
    foreach ($result['counts'] as $table => $count) {
        echo "  {$table}: {$count}\n";
    }
    exit(0);
}

unset($_SESSION['demo_reset_csrf']);
$_SESSION['demo_notice'] = 'Demo data reset — back to the seeded state.';
header('Location: admin/index.php');
exit;
