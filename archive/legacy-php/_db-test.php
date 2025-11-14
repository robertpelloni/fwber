<?php
/**
 * Test Database Connection using SQLite
 * For testing without MySQL dependency
 */

require_once('_secrets.php');

// Use SQLite for testing when MySQL PDO driver is unavailable
$dsn = "sqlite:fwber-test.db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);

    // Create tables if they don't exist (SQLite compatible schema)
    require_once('test-sqlite.php');

} catch (\PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed");
}

// Test database functions (compatible with existing code)
function getUserIdByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : false;
}

function validateSessionOrCookiesReturnLoggedIn() {
    // Simplified for testing - always return true for now
    // In real implementation, this would validate sessions/cookies
    return true;
}

// SecurityManager mock for testing
$securityManager = new class {
    public function validateCsrfToken($token) {
        // Simplified for testing - always return true
        return true;
    }
};
?>
