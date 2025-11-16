<?php
/**
 * Test database connection and basic operations
 */

echo "<h1>FWBer.me Database Connection Test</h1>\n";

try {
    require_once '_init.php';
    echo "✅ _init.php loaded successfully<br>\n";
    
    // Test database connection
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "✅ PDO connection established<br>\n";
        
        // Test basic query
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Database query successful - User count: " . $result['user_count'] . "<br>\n";
        
        // Test ProfileManager
        require_once 'ProfileManager.php';
        $profileManager = new ProfileManager($pdo);
        echo "✅ ProfileManager instantiated successfully<br>\n";
        
        // Test getting a user profile
        $profile = $profileManager->getProfile(1);
        if ($profile) {
            echo "✅ Profile retrieved successfully<br>\n";
            echo "Profile fields: " . count($profile) . " fields<br>\n";
        } else {
            echo "⚠️ No profile found for user ID 1<br>\n";
        }
        
    } else {
        echo "❌ PDO connection not established<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Database Test Complete</h2>\n";
?>
