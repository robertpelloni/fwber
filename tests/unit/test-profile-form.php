<?php
/*
    Test script for profile form functionality
    Run this to verify the profile form is working correctly
*/

require_once('_init.php');
require_once('ProfileManager.php');

echo "<h1>FWBer.me Profile Form Test</h1>\n";

// Test 1: Check if ProfileManager can be instantiated
echo "<h2>Test 1: ProfileManager Instantiation</h2>\n";
try {
    $profileManager = new ProfileManager($pdo);
    echo "✅ ProfileManager instantiated successfully<br>\n";
} catch (Exception $e) {
    echo "❌ ProfileManager failed: " . $e->getMessage() . "<br>\n";
}

// Test 2: Check if we can get a test user profile
echo "<h2>Test 2: Get Test User Profile</h2>\n";
try {
    // Try to get a test user (assuming user ID 1 exists)
    $testProfile = $profileManager->getProfile(1);
    if ($testProfile) {
        echo "✅ Retrieved test profile successfully<br>\n";
        echo "Profile fields: " . implode(', ', array_keys($testProfile)) . "<br>\n";
    } else {
        echo "⚠️ No test profile found (this is normal if no users exist yet)<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Profile retrieval failed: " . $e->getMessage() . "<br>\n";
}

// Test 3: Check database schema
echo "<h2>Test 3: Database Schema Check</h2>\n";
try {
    // Check if users table exists and has expected columns
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Users table columns: " . implode(', ', $columns) . "<br>\n";
    
    // Check if user_preferences table exists
    $stmt = $pdo->query("DESCRIBE user_preferences");
    $prefColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ User_preferences table columns: " . implode(', ', $prefColumns) . "<br>\n";
    
} catch (Exception $e) {
    echo "❌ Database schema check failed: " . $e->getMessage() . "<br>\n";
}

// Test 4: Check CSRF token generation
echo "<h2>Test 4: CSRF Token Generation</h2>\n";
try {
    $token = $securityManager->generateCsrfToken();
    if ($token && strlen($token) === 64) {
        echo "✅ CSRF token generated successfully<br>\n";
        echo "Token: " . substr($token, 0, 16) . "...<br>\n";
    } else {
        echo "❌ CSRF token generation failed<br>\n";
    }
} catch (Exception $e) {
    echo "❌ CSRF token test failed: " . $e->getMessage() . "<br>\n";
}

// Test 5: Check if profile form file exists and is readable
echo "<h2>Test 5: Profile Form File Check</h2>\n";
if (file_exists('profile-form.php')) {
    echo "✅ profile-form.php exists<br>\n";
    $formContent = file_get_contents('profile-form.php');
    if (strpos($formContent, 'b_wantGenderMan') !== false) {
        echo "✅ Profile form contains gender preference fields<br>\n";
    } else {
        echo "❌ Profile form missing gender preference fields<br>\n";
    }
    if (strpos($formContent, 'b_wantBodyTiny') !== false) {
        echo "✅ Profile form contains body preference fields<br>\n";
    } else {
        echo "❌ Profile form missing body preference fields<br>\n";
    }
} else {
    echo "❌ profile-form.php not found<br>\n";
}

// Test 6: Check if edit-profile.php exists and has proper validation
echo "<h2>Test 6: Edit Profile Handler Check</h2>\n";
if (file_exists('edit-profile.php')) {
    echo "✅ edit-profile.php exists<br>\n";
    $handlerContent = file_get_contents('edit-profile.php');
    if (strpos($handlerContent, 'validateCsrfToken') !== false) {
        echo "✅ Edit profile handler has CSRF validation<br>\n";
    } else {
        echo "❌ Edit profile handler missing CSRF validation<br>\n";
    }
    if (strpos($handlerContent, 'b_wantGenderMan') !== false) {
        echo "✅ Edit profile handler processes gender preferences<br>\n";
    } else {
        echo "❌ Edit profile handler missing gender preference processing<br>\n";
    }
} else {
    echo "❌ edit-profile.php not found<br>\n";
}

echo "<h2>Test Summary</h2>\n";
echo "Profile form implementation is ready for testing!<br>\n";
echo "<a href='edit-profile.php'>Go to Profile Form</a><br>\n";
echo "<a href='index.php'>Go to Home</a><br>\n";
?>
