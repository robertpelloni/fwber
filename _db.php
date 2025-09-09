<?php
/*
    Database Connection File
    Uses PDO for a modern, secure database connection.
*/

require_once('_secrets.php');

$dsn = "mysql:host=$dburl;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (\PDOException $e) {
    // In a real app, you'd log this error and show a generic error page.
    error_log($e->getMessage());
    exit('Database connection failed.');
}
?>
