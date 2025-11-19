<?php
/**
 * Cleanup Test Users
 * Removes all test personas and their data from database
 *
 * Usage: php db/cleanup-test-users.php
 */

require_once __DIR__ . '/../_db.php';

echo "FWBer Test User Cleanup\n";
echo "=======================\n\n";

$testEmails = [
    'john.test@example.com',
    'jane.test@example.com',
    'alex.test@example.com'
];

echo "⚠️  WARNING: This will permanently delete test users and all their data!\n";
echo "Test users to delete:\n";
foreach ($testEmails as $email) {
    echo "  - {$email}\n";
}
echo "\n";

// Confirm deletion
echo "Type 'DELETE' to confirm: ";
$confirmation = trim(fgets(STDIN));

if ($confirmation !== 'DELETE') {
    echo "Cleanup cancelled.\n";
    exit(0);
}

echo "\nDeleting test users...\n\n";

foreach ($testEmails as $email) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Deleting: {$user['username']} (ID: {$user['id']})\n";

        // Delete user (CASCADE will delete related data automatically)
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$user['id']]);

        echo "  ✅ Deleted user and all related data\n";
    } else {
        echo "Skipping: {$email} (not found)\n";
    }
}

echo "\n✅ Test user cleanup complete!\n";
