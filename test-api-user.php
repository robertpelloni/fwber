<?php
// test-api-user.php
// Test Laravel API protected user endpoint

$token = file_get_contents('test-token.txt');
if (!$token) {
    echo "Error: No token found. Please run test-api-login.php first.\n";
    exit(1);
}

$url = 'http://127.0.0.1:8000/api/user';
$options = [
    'http' => [
        'header' => "Authorization: Bearer " . trim($token) . "\r\nAccept: application/json\r\n",
        'method' => 'GET'
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error: " . error_get_last()['message'] . "\n";
} else {
    echo "Response: " . $result . "\n";
}

// Also check HTTP response headers
$headers = $http_response_header ?? [];
echo "\nHeaders:\n";
foreach ($headers as $header) {
    echo "  " . $header . "\n";
}
?>
