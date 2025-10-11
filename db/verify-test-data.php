<?php
/**
 * Verify Test Data Persistence
 * Checks that profile data was saved correctly for all test personas
 *
 * Usage: php db/verify-test-data.php
 */

require_once __DIR__ . '/../_db.php';

echo "FWBer Test Data Verification\n";
echo "============================\n\n";

$testEmails = [
    'john.test@example.com',
    'jane.test@example.com',
    'alex.test@example.com'
];

$allPassed = true;

foreach ($testEmails as $email) {
    echo "Testing: {$email}\n";
    echo str_repeat('-', 50) . "\n";

    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ User not found\n\n";
        $allPassed = false;
        continue;
    }

    echo "✅ User found (ID: {$user['id']})\n";
    echo "  - Username: {$user['username']}\n";
    echo "  - Age: {$user['age']}\n";
    echo "  - Gender: {$user['gender']}\n";

    // Check gender-specific attributes
    if ($user['gender'] === 'male') {
        // Male should have penisSize and bodyHair, but NOT breastSize
        $pubicHair = $user['pubicHair'] ?? 'NOT SET';
        echo "  - Pubic Hair: {$pubicHair}\n";

        $penisSize = $user['penisSize'] ?? 'NOT SET';
        echo "  - Penis Size: {$penisSize}\n";

        $bodyHair = $user['bodyHair'] ?? 'NOT SET';
        echo "  - Body Hair: {$bodyHair}\n";

        if (isset($user['breastSize'])) {
            echo "  ❌ ERROR: Male user has breastSize set (data leakage!)\n";
            $allPassed = false;
        } else {
            echo "  ✅ Breast Size: NULL (correct for male)\n";
        }

    } elseif ($user['gender'] === 'female') {
        // Female should have breastSize, but NOT penisSize or bodyHair
        $pubicHair = $user['pubicHair'] ?? 'NOT SET';
        echo "  - Pubic Hair: {$pubicHair}\n";

        $breastSize = $user['breastSize'] ?? 'NOT SET';
        echo "  - Breast Size: {$breastSize}\n";

        if (isset($user['penisSize']) || isset($user['bodyHair'])) {
            echo "  ❌ ERROR: Female user has male-specific fields set (data leakage!)\n";
            $allPassed = false;
        } else {
            echo "  ✅ Penis Size: NULL (correct for female)\n";
            echo "  ✅ Body Hair: NULL (correct for female)\n";
        }

    } elseif ($user['gender'] === 'non-binary') {
        // Non-binary can have any combination
        $pubicHair = $user['pubicHair'] ?? 'NOT SET';
        $penisSize = $user['penisSize'] ?? 'NOT SET';
        $bodyHair = $user['bodyHair'] ?? 'NOT SET';
        $breastSize = $user['breastSize'] ?? 'NOT SET';

        echo "  - Pubic Hair: {$pubicHair}\n";
        echo "  - Penis Size: {$penisSize}\n";
        echo "  - Body Hair: {$bodyHair}\n";
        echo "  - Breast Size: {$breastSize}\n";
        echo "  ✅ Non-binary user can have any combination\n";
    }

    // Check user_preferences table
    $prefStmt = $pdo->prepare("
        SELECT COUNT(*) as pref_count
        FROM user_preferences
        WHERE user_id = ?
    ");
    $prefStmt->execute([$user['id']]);
    $prefCount = $prefStmt->fetch(PDO::FETCH_ASSOC)['pref_count'];

    echo "  - Preferences saved: {$prefCount}\n";

    if ($prefCount > 0) {
        echo "  ✅ User has preferences saved\n";

        // Show sample preferences
        $sampleStmt = $pdo->prepare("
            SELECT preference_key, preference_value
            FROM user_preferences
            WHERE user_id = ?
            LIMIT 5
        ");
        $sampleStmt->execute([$user['id']]);
        $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

        echo "  Sample preferences:\n";
        foreach ($samples as $pref) {
            echo "    - {$pref['preference_key']}: {$pref['preference_value']}\n";
        }
    } else {
        echo "  ⚠️  No preferences saved yet\n";
    }

    echo "\n";
}

echo str_repeat('=', 50) . "\n";
if ($allPassed) {
    echo "✅ ALL TESTS PASSED\n";
    echo "No data leakage detected, gender-specific fields validated.\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "Check the output above for specific errors.\n";
    exit(1);
}
