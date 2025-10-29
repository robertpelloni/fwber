<?php
/**
 * Generate Test Users for SQLite Testing
 * Creates 3 test personas for E2E testing
 */

echo "FWBer Test User Generator (SQLite Version)\n";
echo "==========================================\n\n";

// Include test database configuration
require_once(__DIR__ . '/../_db-test.php');

// Test password for all users
$testPassword = 'TestPass123!';
$passwordSalt = bin2hex(random_bytes(32));
$passwordHash = password_hash($testPassword . $passwordSalt, PASSWORD_ARGON2ID);

echo "Test Password: $testPassword\n";
echo "Password Hash: " . substr($passwordHash, 0, 50) . "...\n";
echo "Password Salt: " . substr($passwordSalt, 0, 16) . "...\n\n";

// Test personas data
$testUsers = [
    [
        'username' => 'JohnTest',
        'email' => 'john.test@example.com',
        'age' => 28,
        'gender' => 'male',
        'seeking_gender' => 'any',
        'relationship_type' => 'casual',
        'pubicHair' => 'trimmed',
        'penisSize' => 'average',
        'bodyHair' => 'some',
        // breastSize intentionally omitted for male user
    ],
    [
        'username' => 'JaneTest',
        'email' => 'jane.test@example.com',
        'age' => 25,
        'gender' => 'female',
        'seeking_gender' => 'any',
        'relationship_type' => 'relationship',
        'pubicHair' => 'shaved',
        'breastSize' => 'c-cup',
        // penisSize and bodyHair intentionally omitted for female user
    ],
    [
        'username' => 'AlexTest',
        'email' => 'alex.test@example.com',
        'age' => 30,
        'gender' => 'non-binary',
        'seeking_gender' => 'any',
        'relationship_type' => 'any',
        'pubicHair' => 'natural',
        'penisSize' => 'thick',      // Non-binary can have any combination
        'bodyHair' => 'minimal',
        'breastSize' => 'average',    // Non-binary can have any combination
    ]
];

// Insert test users
foreach ($testUsers as $userData) {
    try {
        echo "Creating test user: {$userData['username']} ({$userData['email']})\n";

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                email, username, password_hash, password_salt, email_verified,
                age, gender, seeking_gender, relationship_type,
                pubicHair, penisSize, bodyHair, breastSize
            ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userData['email'],
            $userData['username'],
            $passwordHash,
            $passwordSalt,
            $userData['age'],
            $userData['gender'],
            $userData['seeking_gender'],
            $userData['relationship_type'],
            $userData['pubicHair'] ?? '',
            $userData['penisSize'] ?? '',
            $userData['bodyHair'] ?? '',
            $userData['breastSize'] ?? ''
        ]);

        $userId = $pdo->lastInsertId();
        echo "  ✅ Created user ID: $userId\n";

        // Add some sample preferences for each user
        $samplePreferences = [
            'b_wantTattoosSome', 'b_wantLooksAverage', 'b_wantIntelligenceHigh',
            'b_wantBedroomPersonalityAdventurous', 'b_wantPubicHairTrimmed',
            'b_wantAgeFrom18', 'b_wantAgeTo35'
        ];

        foreach ($samplePreferences as $pref) {
            $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, preference_name, preference_value) VALUES (?, ?, 1)");
            $stmt->execute([$userId, $pref]);
        }

        echo "  ✅ Added {$userData['username']} sample preferences\n";

    } catch (Exception $e) {
        echo "  ❌ Error creating {$userData['username']}: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Test users created successfully!\n";
echo "\nTest Credentials (all users):\n";
echo "- Password: `$testPassword`\n";
echo "\nNext Steps:\n";
echo "1. Run: php db/verify-test-data.php\n";
echo "2. Follow: TESTING_QUICKSTART.md for manual E2E testing\n";
echo "3. Test profile forms with each persona\n";

?>
