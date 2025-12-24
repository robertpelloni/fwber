<?php
require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$key = $_ENV['MERCURE_SUBSCRIBER_JWT_KEY'] ?? '';
$token = $argv[1] ?? '';

if (empty($key)) {
    echo "Error: MERCURE_SUBSCRIBER_JWT_KEY not set in .env\n";
    exit(1);
}

if (empty($token)) {
    echo "Usage: php verify_mercure_jwt.php <token>\n";
    exit(1);
}

echo "Using Key: " . substr($key, 0, 5) . "..." . substr($key, -5) . "\n";
echo "Key Length: " . strlen($key) . "\n";

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    echo "SUCCESS: Token signature verified!\n";
    print_r($decoded);
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
    
    // Try to debug if it's a base64 issue
    echo "\n--- Debugging ---\n";
    try {
        $decodedKey = base64_decode($key, true);
        // Check if it strictly decodes to something else
        if ($decodedKey !== false && $decodedKey !== $key) {
             JWT::decode($token, new Key($decodedKey, 'HS256'));
             echo "WARNING: Token verified using BASE64 DECODED key.\n";
             echo "This means the token was signed with the binary version of your key, but your .env has the base64 string.\n";
             echo "Solution: Change your .env key to a plain string (e.g. 'mysecretkey') to avoid ambiguity.\n";
        }
    } catch (Exception $e2) {
        // echo "Debug check failed: " . $e2->getMessage() . "\n";
    }
}
