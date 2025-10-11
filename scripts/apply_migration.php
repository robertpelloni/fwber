<?php
// scripts/apply_migration.php
// Applies the legacy matcher compatibility migration using PDO.
// Tries _secrets.php first, then .env DB_* if available.

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

// Load .env if exists (non-fatal if missing)
try {
    Dotenv\Dotenv::createImmutable($root)->load();
} catch (Throwable $e) {
    // ignore
}

$dbHost = $_ENV['DB_HOST'] ?? null;
$dbName = $_ENV['DB_NAME'] ?? null;
$dbUser = $_ENV['DB_USER'] ?? null;
$dbPass = $_ENV['DB_PASS'] ?? null;

// Try to include legacy _secrets.php for DB vars if present
$secrets = $root . '/_secrets.php';
if (file_exists($secrets)) {
    // _secrets.php should define $dburl (host), $dbuser, $dbpass, $dbname
    require $secrets;
    $dbHost = $dburl ?? $dbHost;
    $dbName = $dbname ?? $dbName;
    $dbUser = $dbuser ?? $dbUser;
    $dbPass = $dbpass ?? $dbPass;
}

if (!$dbHost || !$dbName || !$dbUser) {
    fwrite(STDERR, "Missing DB credentials. Set DB_* in .env or provide _secrets.php.\n");
    exit(2);
}

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connect failed: " . $e->getMessage() . "\n");
    exit(3);
}

$migrationPath = $root . '/db/migrations/2025-10-11-legacy-matcher-compat.sql';
if (!file_exists($migrationPath)) {
    fwrite(STDERR, "Migration file not found: {$migrationPath}\n");
    exit(4);
}

$sql = file_get_contents($migrationPath);
if ($sql === false) {
    fwrite(STDERR, "Could not read migration file.\n");
    exit(5);
}

// Split by semicolons, but skip comments and empty statements
$statements = array_filter(array_map('trim', preg_split('/;\s*\n|;\s*$/m', $sql)));

$applied = 0;
$pdo->beginTransaction();
try {
    foreach ($statements as $stmt) {
        $s = trim($stmt);
        if ($s === '' || str_starts_with($s, '--') || str_starts_with($s, '/*')) {
            continue;
        }
        $pdo->exec($s);
        $applied++;
    }
    $pdo->commit();
    echo "Applied {$applied} migration statements successfully.\n";
    exit(0);
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(6);
}
