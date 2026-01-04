<?php
// fix-database-user.php
// Script to fix database user creation issues

echo "Fixing fwber database user...\n";

// Try to connect as root first
$root_password = ''; // You may need to set this
$root_host = '127.0.0.1';

try {
    // Try without password first
    $pdo = new PDO("mysql:host=$root_host", 'root', '');
    echo "✅ Connected as root (no password)\n";
} catch (PDOException $e) {
    try {
        // Try with common passwords
        $passwords = ['root', 'password', 'admin', 'Temppass0!'];
        foreach ($passwords as $pwd) {
            try {
                $pdo = new PDO("mysql:host=$root_host", 'root', $pwd);
                echo "✅ Connected as root with password: $pwd\n";
                $root_password = $pwd;
                break;
            } catch (PDOException $e2) {
                continue;
            }
        }
        if (!$root_password) {
            throw new Exception("Could not connect as root with any common password");
        }
    } catch (Exception $e3) {
        echo "❌ Could not connect as root: " . $e3->getMessage() . "\n";
        exit(1);
    }
}

try {
    // Drop user if exists
    $pdo->exec("DROP USER IF EXISTS 'fwber'@'localhost'");
    echo "✅ Dropped existing fwber user\n";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS fwber CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Created/verified fwber database\n";
    
    // Create user with proper privileges
    $pdo->exec("CREATE USER 'fwber'@'localhost' IDENTIFIED BY 'Temppass0!'");
    echo "✅ Created fwber user\n";
    
    // Grant privileges
    $pdo->exec("GRANT ALL PRIVILEGES ON fwber.* TO 'fwber'@'localhost'");
    echo "✅ Granted privileges to fwber user\n";
    
    // Flush privileges
    $pdo->exec("FLUSH PRIVILEGES");
    echo "✅ Flushed privileges\n";
    
    // Test the new user
    $test_pdo = new PDO("mysql:host=127.0.0.1;dbname=fwber", 'fwber', 'Temppass0!');
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Successfully tested fwber user connection\n";
    
    // Show databases
    $result = $test_pdo->query("SHOW DATABASES");
    echo "✅ Available databases:\n";
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        echo "  - " . $row[0] . "\n";
    }
    
    echo "\n🎉 Database user setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}
?>
