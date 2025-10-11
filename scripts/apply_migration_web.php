<?php
// scripts/apply_migration_web.php
// Purpose: Safely apply the legacy matcher compatibility migration via browser
// Guards: Requires user login and DEBUG_MODE=true in environment

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/_init.php'; // initializes $pdo and $securityManager

// Require login
if (!validateSessionOrCookiesReturnLoggedIn()) {
    http_response_code(403);
    echo 'Forbidden: login required';
    exit;
}

// Require DEBUG_MODE enabled
$debug = getenv('DEBUG_MODE') ?: ($_ENV['DEBUG_MODE'] ?? '');
if (!in_array(strtolower((string)$debug), ['1','true','yes'], true)) {
    http_response_code(403);
    echo 'Forbidden: DEBUG_MODE must be enabled to run migrations via web.';
    exit;
}

$migrationPath = $root . '/db/migrations/2025-10-11-legacy-matcher-compat.sql';
if (!file_exists($migrationPath)) {
    http_response_code(500);
    echo 'Migration file not found.';
    exit;
}

$sql = file_get_contents($migrationPath);
if ($sql === false) {
    http_response_code(500);
    echo 'Could not read migration file.';
    exit;
}

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
    header('Content-Type: text/plain');
    echo "Applied {$applied} migration statements successfully.";
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Migration failed: ' . $e->getMessage();
}
