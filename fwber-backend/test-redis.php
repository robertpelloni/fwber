<?php

echo "Testing raw Redis connection...\n";

try {
    $redis = new Redis();
    $host = getenv('REDIS_HOST') ?: 'fwber-redis';
    $port = getenv('REDIS_PORT') ?: 6379;
    
    echo "Connecting to $host:$port...\n";
    
    if ($redis->connect($host, $port, 2)) {
        echo "Successfully connected to Redis!\n";
        echo "Ping response: " . $redis->ping() . "\n";
    } else {
        echo "Failed to connect to Redis.\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
