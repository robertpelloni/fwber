<?php
header('Content-Type: text/plain');

echo "Debug Redis Connection (Web Context)\n";
echo "------------------------------------\n";

// 1. Check Environment Variables
$envHost = getenv('REDIS_HOST');
$envPort = getenv('REDIS_PORT');
echo "getenv('REDIS_HOST'): " . var_export($envHost, true) . "\n";
echo "getenv('REDIS_PORT'): " . var_export($envPort, true) . "\n";

// 2. Check $_SERVER (sometimes Docker env vars end up here)
echo "\$_SERVER['REDIS_HOST']: " . var_export($_SERVER['REDIS_HOST'] ?? 'NOT SET', true) . "\n";

$host = $envHost ?: ($_SERVER['REDIS_HOST'] ?? '127.0.0.1');
$port = $envPort ?: ($_SERVER['REDIS_PORT'] ?? 6379);

echo "Using Host: $host\n";
echo "Using Port: $port\n";

// 3. Test Connection
try {
    if (!class_exists('Redis')) {
        die("Error: Redis class not found. Is phpredis extension installed?\n");
    }

    $redis = new Redis();
    echo "Connecting...\n";
    
    // Low timeout for web test
    $connected = $redis->connect($host, $port, 2); 
    
    if ($connected) {
        echo "Connection: SUCCESS\n";
        echo "Ping: " . $redis->ping() . "\n";
    } else {
        echo "Connection: FAILED\n";
        echo "Last Error: " . $redis->getLastError() . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
