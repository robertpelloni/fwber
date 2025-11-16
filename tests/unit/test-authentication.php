<?php
/**
 * Test authentication system
 */

require_once '_init.php';
require_once 'security-manager.php';

echo "<h1>FWBer.me Authentication Test</h1>\n";
echo "<h2>Testing Security Manager</h2>\n";

try {
    // Test SecurityManager instantiation
    $securityManager = new SecurityManager($pdo);
    echo "✅ SecurityManager instantiated successfully<br>\n";
    
    // Test CSRF token generation
    $csrfToken = $securityManager->generateCSRFToken();
    echo "✅ CSRF token generated: " . substr($csrfToken, 0, 20) . "...<br>\n";
    
    // Test password hashing
    $testPassword = "test123";
    $passwordData = $securityManager->hashPassword($testPassword);
    echo "✅ Password hashed successfully<br>\n";
    
    // Test password verification
    $isValid = $securityManager->verifyPassword($testPassword, $passwordData['hash'], $passwordData['salt']);
    echo "✅ Password verification: " . ($isValid ? "PASS" : "FAIL") . "<br>\n";
    
    // Test session validation
    $isLoggedIn = validateSessionOrCookiesReturnLoggedIn();
    echo "✅ Session validation: " . ($isLoggedIn ? "LOGGED IN" : "NOT LOGGED IN") . "<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Authentication Test Complete</h2>\n";
echo "<a href='signin.php'>Go to Sign In</a><br>\n";
echo "<a href='join.php'>Go to Join</a><br>\n";
?>
