<?php

require 'fwber-backend/vendor/autoload.php';

use App\Services\MercurePublisher;

// Mock config
function config($key) {
    if ($key === 'services.mercure.subscriber_key') return '!ChangeMe!';
    if ($key === 'services.mercure.publisher_key') return '!ChangeMe!';
    return null;
}

// Instantiate and test
$publisher = new MercurePublisher();
$token = $publisher->generateSubscriberJWT(['https://fwber.me/users/1'], 1);

echo "Generated Token: " . $token . "\n";

// Verify structure
$parts = explode('.', $token);
if (count($parts) !== 3) {
    echo "Error: Invalid JWT structure\n";
    exit(1);
}

echo "Structure OK\n";
