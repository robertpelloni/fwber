<?php
/**
 * Verify Test Data for SQLite Testing
 * Validates that test users and their data are correctly stored
 */

echo "FWBer Test Data Verification (SQLite Version)\n";
echo "=============================================\n\n";

// Include test database configuration
require_once(__DIR__ . '/../_db-test.php');

// Test each user
$testUsers = [
    'john.test@example.com',
    'jane.test@example.com',
    'alex.test@example.com'
];

foreach ($testUsers as $email) {
    echo "Testing: $email\n";
    echo "--------------------------------------------------\n";

    try {
        // Get user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo "❌ User not found\n";
            continue;
        }

        echo "✅ User found (ID: {$user['id']})\n";
        echo "  - Username: {$user['username']}\n";
        echo "  - Age: {$user['age']}\n";
        echo "  - Gender: {$user['gender']}\n";

        // Check gender-specific fields
        if ($user['gender'] === 'male') {
            echo "  - Pubic Hair: {$user['pubicHair']}\n";
            echo "  - Penis Size: {$user['penisSize']}\n";
            echo "  - Body Hair: {$user['bodyHair']}\n";
            if (!empty($user['breastSize'])) {
                echo "  ⚠️  WARNING: Male user has breastSize set: {$user['breastSize']}\n";
            } else {
                echo "  ✅ Breast Size: NULL (correct for male)\n";
            }
        } elseif ($user['gender'] === 'female') {
            echo "  - Pubic Hair: {$user['pubicHair']}\n";
            echo "  - Breast Size: {$user['breastSize']}\n";
            if (!empty($user['penisSize'])) {
                echo "  ⚠️  WARNING: Female user has penisSize set: {$user['penisSize']}\n";
            } else {
                echo "  ✅ Penis Size: NULL (correct for female)\n";
            }
            if (!empty($user['bodyHair'])) {
                echo "  ⚠️  WARNING: Female user has bodyHair set: {$user['bodyHair']}\n";
            } else {
                echo "  ✅ Body Hair: NULL (correct for female)\n";
            }
        } elseif ($user['gender'] === 'non-binary') {
            echo "  - Pubic Hair: {$user['pubicHair']}\n";
            echo "  - Penis Size: {$user['penisSize']}\n";
            echo "  - Body Hair: {$user['bodyHair']}\n";
            echo "  - Breast Size: {$user['breastSize']}\n";
            echo "  ✅ Non-binary user can have any combination\n";
        }

        // Check preferences
        $stmt = $pdo->prepare("SELECT COUNT(*) as pref_count FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $prefCount = $stmt->fetch()['pref_count'];

        echo "  - Preferences saved: $prefCount\n";
        if ($prefCount > 0) {
            echo "  ✅ User has preferences saved\n";
        } else {
            echo "  ⚠️  WARNING: No preferences found\n";
        }

    } catch (Exception $e) {
        echo "❌ Error testing user: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

// Database stats
echo "Database Statistics:\n";
echo "===================\n";
$stats = $testDb->getStats();
foreach ($stats as $table => $count) {
    echo ucfirst($table) . ": $count\n";
}

echo "\n✅ Test data verification complete!\n";
echo "\nNext Steps:\n";
echo "1. Start PHP development server: php -S localhost:8000\n";
echo "2. Open http://localhost:8000 in browser\n";
echo "3. Follow TESTING_QUICKSTART.md for manual E2E testing\n";

?>
