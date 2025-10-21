<?php
// test-api-register.php
// Test Laravel API registration endpoint

$url = 'http://127.0.0.1:8000/api/auth/register';
$data = [
    'name' => 'Test User',
    'email' => 'test@fwber.me',
    'password' => 'password123',
    'password_confirmation' => 'password123'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\nAccept: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
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
echo "Headers:\n";
foreach ($headers as $header) {
    echo "  " . $header . "\n";
}
?>
