<?php

require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mercureUrl = $_ENV['MERCURE_PUBLISHER_URL'] ?? 'http://127.0.0.1:3001/.well-known/mercure';
$jwtKey = $_ENV['MERCURE_SUBSCRIBER_JWT_KEY'];

if (!$jwtKey) {
    die("Error: MERCURE_SUBSCRIBER_JWT_KEY not set in .env\n");
}

echo "Testing Mercure Subscriber...\n";
echo "URL: $mercureUrl\n";
echo "Key: " . substr($jwtKey, 0, 5) . "...\n";

// 1. Generate Subscriber JWT
// Must match the claims expected by Mercure for the requested topic
$topic = 'https://fwber.com/test';
$payload = [
    'mercure' => [
        'subscribe' => [$topic]
    ],
    'sub' => 'test-user',
    'exp' => time() + 60,
    'iat' => time()
];

$jwt = JWT::encode($payload, $jwtKey, 'HS256');
echo "Generated JWT: " . substr($jwt, 0, 10) . "...\n";

// 2. Connect to Mercure (SSE)
$url = $mercureUrl . '?topic=' . urlencode($topic);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $jwt",
    "Accept: text/event-stream"
]);
// Set a timeout because SSE is a streaming connection
curl_setopt($ch, CURLOPT_TIMEOUT, 5); 

echo "Connecting to $url...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Response Code: $httpCode\n";
if ($error) {
    echo "Curl Error: $error\n";
}
// Note: A successful SSE connection might timeout (Operation timed out) because it stays open.
// But a 401 would return immediately.

if ($httpCode == 200) {
    echo "SUCCESS: Mercure accepted the subscriber connection (200 OK).\n";
} elseif ($httpCode == 401) {
    echo "FAILURE: 401 Unauthorized. The subscriber key is rejected.\n";
} else {
    echo "Response Body (first 100 chars): " . substr($response, 0, 100) . "\n";
}
