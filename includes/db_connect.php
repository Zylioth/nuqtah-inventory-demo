<?php
// Function to load .env variables into $_ENV
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        
        // Only split if an equal sign exists
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            putenv(trim($name) . "=" . trim($value)); // Also sets environment variable
        }
    }
}

// Trigger the load - assuming .env is in your project root
loadEnv(__DIR__ . '/../.env');

// Read-only demo guard. No-op unless DEMO_MODE=true, so the same codebase
// still runs fully functional locally.
require_once __DIR__ . '/demo_mode.php';
demo_guard();

// Database config.
// .env wins when present (local XAMPP). Otherwise fall back to the MYSQL*
// variables injected by managed hosts such as Railway, so the same file
// deploys without edits.
$host    = $_ENV['DB_HOST'] ?? (getenv('MYSQLHOST') ?: 'localhost');
$db      = $_ENV['DB_NAME'] ?? (getenv('MYSQLDATABASE') ?: 'nuqtah_inv');
$user    = $_ENV['DB_USER'] ?? (getenv('MYSQLUSER') ?: 'root');
$pass    = $_ENV['DB_PASS'] ?? (getenv('MYSQLPASSWORD') ?: ''); // XAMPP default is empty
$port    = $_ENV['DB_PORT'] ?? (getenv('MYSQLPORT') ?: '3306');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Logs Activity (Keep this exactly as it was)
function logActivity($pdo, $admin_id, $action) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_taken, timestamp) VALUES (?, ?, NOW())");
        $stmt->execute([$admin_id, $action]);
    } catch (PDOException $e) {
        error_log("Logging failed: " . $e->getMessage());
    }
}
?>