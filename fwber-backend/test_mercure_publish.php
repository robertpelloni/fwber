<?php

require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mercureUrl = $_ENV['MERCURE_PUBLISHER_URL'] ?? 'http://127.0.0.1:3001/.well-known/mercure';
$jwtKey = $_ENV['MERCURE_PUBLISHER_JWT_KEY'];

if (!$jwtKey) {
    die("Error: MERCURE_PUBLISHER_JWT_KEY not set in .env\n");
}

echo "Testing Mercure Publisher...\n";
echo "URL: $mercureUrl\n";
echo "Key: " . substr($jwtKey, 0, 5) . "...\n";

// 1. Generate JWT using firebase/php-jwt
$payload = [
    'mercure' => [
        'publish' => ['*']
    ]
];

// firebase/php-jwt requires the key to be passed directly for HS256
$jwt = JWT::encode($payload, $jwtKey, 'HS256');
echo "Generated JWT: " . substr($jwt, 0, 10) . "...\n";

// 2. Send POST request to Mercure
$ch = curl_init($mercureUrl);
$postData = http_build_query([
    'topic' => 'https://fwber.com/test',
    'data' => json_encode(['status' => 'ok', 'time' => time()])
]);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $jwt",
    "Content-Type: application/x-www-form-urlencoded"
]);
// curl_setopt($ch, CURLOPT_VERBOSE, true); // Uncomment for debug

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response Body: $response\n";

if ($httpCode == 200) {
    echo "SUCCESS: Mercure accepted the publish request.\n";
} elseif ($httpCode == 401) {
    echo "FAILURE: 401 Unauthorized. The key is still rejected.\n";
} else {
    echo "FAILURE: Unexpected error.\n";
}
