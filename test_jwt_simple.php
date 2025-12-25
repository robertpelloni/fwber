<?php

require 'fwber-backend/vendor/autoload.php';

use Firebase\JWT\JWT;

$key = '!ChangeMe!';
$payload = [
    'mercure' => [
        'subscribe' => ['https://fwber.me/users/1']
    ]
];

// Encode using the library (which we know fails locally due to key length, but let's see if we can force it or use a different method)
// Actually, I want to verify if my custom encoding matches what JWT.io would produce or if there's a subtle difference.

function urlsafeB64Encode($input) {
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}

function encodeJWT($payload, $key) {
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $segments = [];
    $segments[] = urlsafeB64Encode(json_encode($header));
    $segments[] = urlsafeB64Encode(json_encode($payload));
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, $key, true);
    $segments[] = urlsafeB64Encode($signature);
    return implode('.', $segments);
}

echo encodeJWT($payload, $key) . "\n";
