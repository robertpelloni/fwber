<?php
// test-api-login.php
// Test Laravel API login endpoint

$url = 'http://127.0.0.1:8000/api/auth/login';
$data = [
    'email' => 'test@fwber.me',
    'password' => 'password123'
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
    
    // Parse the response to extract the token
    $response = json_decode($result, true);
    if (isset($response['token'])) {
        echo "\nToken: " . $response['token'] . "\n";
        file_put_contents('test-token.txt', $response['token']);
        echo "Token saved to test-token.txt\n";
    }
}

// Also check HTTP response headers
$headers = $http_response_header ?? [];
echo "\nHeaders:\n";
foreach ($headers as $header) {
    echo "  " . $header . "\n";
}
?>
