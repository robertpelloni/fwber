<?php
/**
 * Generate Test Users for E2E Testing
 * Creates three test personas with proper Argon2ID password hashes
 *
 * Usage: php db/generate-test-users.php
 */

require_once __DIR__ . '/../_db.php';

// Test password (same for all test users for convenience)
$testPassword = 'TestPass123!';

// Test personas
$testUsers = [
    [
        'email' => 'john.test@example.com',
        'username' => 'JohnTest',
        'age' => 28,
        'gender' => 'male',
        'description' => 'Male user test persona'
    ],
    [
        'email' => 'jane.test@example.com',
        'username' => 'JaneTest',
        'age' => 25,
        'gender' => 'female',
        'description' => 'Female user test persona'
    ],
    [
        'email' => 'alex.test@example.com',
        'username' => 'AlexTest',
        'age' => 30,
        'gender' => 'non-binary',
        'description' => 'Non-binary user test persona'
    ]
];

echo "FWBer Test User Generator\n";
echo "=========================\n\n";

// Generate password hash and salt
$passwordSalt = bin2hex(random_bytes(32));
$passwordHash = password_hash($testPassword . $passwordSalt, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 2
]);

echo "Test Password: {$testPassword}\n";
echo "Password Hash: {$passwordHash}\n";
echo "Password Salt: {$passwordSalt}\n\n";

// Create or clean test users
foreach ($testUsers as $user) {
    echo "Creating test user: {$user['username']} ({$user['email']})\n";

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$user['email']]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Delete existing test user and all their data
        echo "  - Deleting existing user (ID: {$existingUser['id']})\n";
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$existingUser['id']]);
    }

    // Create new test user
    $insertStmt = $pdo->prepare("
        INSERT INTO users (
            email, username, password_hash, password_salt,
            email_verified, age, gender, created_at, active
        ) VALUES (
            ?, ?, ?, ?,
            1, ?, ?, NOW(), 1
        )
    ");

    $insertStmt->execute([
        $user['email'],
        $user['username'],
        $passwordHash,
        $passwordSalt,
        $user['age'],
        $user['gender']
    ]);

    $userId = $pdo->lastInsertId();
    echo "  ✅ Created user ID: {$userId}\n";
}

echo "\n✅ Test users created successfully!\n\n";
echo "Test Credentials (all users):\n";
echo "Password: {$testPassword}\n\n";

echo "Test Personas:\n";
foreach ($testUsers as $user) {
    echo "- {$user['username']}: {$user['email']} ({$user['description']})\n";
}

echo "\n";
echo "Next Steps:\n";
echo "1. Run E2E tests using these credentials\n";
echo "2. Each persona tests different profile form field visibility\n";
echo "3. Check database after tests to verify data persistence\n";
