<?php
/*
    Database Connection File
    Uses PDO for a modern, secure database connection.
    Modified for testing with SQLite when MySQL has authentication issues.
*/

require_once('_secrets.php');

// Try MySQL first, fall back to SQLite for testing
try {
    $dsn = "mysql:host=$dburl;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (\PDOException $mysqlError) {
    // Fallback to SQLite for testing
    try {
        $sqlitePath = __DIR__ . '/db/fwber_test.db';
        $dsn = "sqlite:$sqlitePath";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $pdo = new PDO($dsn, null, null, $options);

        // Create tables if they don't exist (simplified schema for testing)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                age INTEGER,
                gender TEXT,
                body TEXT,
                ethnicity TEXT,
                hairColor TEXT,
                hairLength TEXT,
                tattoos TEXT,
                overallLooks TEXT,
                intelligence TEXT,
                bedroomPersonality TEXT,
                pubicHair TEXT,
                penisSize TEXT,
                bodyHair TEXT,
                breastSize TEXT,
                zip_code TEXT,
                max_distance INTEGER DEFAULT 50,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                preference_key TEXT NOT NULL,
                preference_value TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

    } catch (\PDOException $sqliteError) {
        error_log("MySQL Error: " . $mysqlError->getMessage());
        error_log("SQLite Error: " . $sqliteError->getMessage());
        exit('Database connection failed. Check error logs.');
    }
}
?>
