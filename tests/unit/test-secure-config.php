<?php
/*
 * Test script for secure configuration system
 * Phase 1B: Verify secure configuration loading works correctly
 */

require_once '_init.php';

echo "=== Secure Configuration Test ===\n\n";

// Test database configuration
echo "Database Configuration:\n";
echo "Host: " . (SecureConfig::get('db_host') ?: 'NOT SET') . "\n";
echo "Name: " . (SecureConfig::get('db_name') ?: 'NOT SET') . "\n";
echo "User: " . (SecureConfig::get('db_user') ?: 'NOT SET') . "\n";
echo "Pass: " . (SecureConfig::get('db_pass') ? '[HIDDEN]' : 'NOT SET') . "\n\n";

// Test security configuration
echo "Security Configuration:\n";
echo "Encryption Key: " . (SecureConfig::get('encryption_key') ? '[SET]' : 'NOT SET') . "\n";
echo "JWT Secret: " . (SecureConfig::get('jwt_secret') ? '[SET]' : 'NOT SET') . "\n\n";

// Test site configuration
echo "Site Configuration:\n";
echo "Site Name: " . (SecureConfig::get('site_name') ?: 'NOT SET') . "\n";
echo "Site URL: " . (SecureConfig::get('site_url') ?: 'NOT SET') . "\n\n";

// Test legacy compatibility
echo "Legacy Compatibility Test:\n";
echo "Global \$dburl: " . (isset($dburl) ? $dburl : 'NOT SET') . "\n";
echo "Global \$dbname: " . (isset($dbname) ? $dbname : 'NOT SET') . "\n";
echo "Global \$dbuser: " . (isset($dbuser) ? $dbuser : 'NOT SET') . "\n";
echo "Global \$dbpass: " . (isset($dbpass) ? '[HIDDEN]' : 'NOT SET') . "\n";
echo "Global \$encryptionKey: " . (isset($encryptionKey) ? '[SET]' : 'NOT SET') . "\n\n";

echo "=== Test Complete ===\n";
?>
