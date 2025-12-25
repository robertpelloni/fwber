<?php

require 'fwber-backend/vendor/autoload.php';

use Firebase\JWT\JWT;

$key = '!ChangeThisMercureHubJWTSecretKey!'; // The key from the docs
$payload = [
    'mercure' => [
        'subscribe' => ['https://fwber.me/users/1']
    ]
];

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
