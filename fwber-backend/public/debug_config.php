<?php
// public/debug_config.php
// Purpose: Verify what Laravel config and Env vars the WEB SERVER sees
header('Content-Type: text/plain');

try {
    // Bootstrap Laravel core (minimal) to get config
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    
    echo "--- Environment Variables (getenv) ---\n";
    echo "REDIS_HOST: " . var_export(getenv('REDIS_HOST'), true) . "\n";
    echo "REDIS_PORT: " . var_export(getenv('REDIS_PORT'), true) . "\n";
    
    echo "\n--- Laravel Configuration (config) ---\n";
    $config = config('database.redis.default');
    echo "Host: " . ($config['host'] ?? 'N/A') . "\n";
    echo "Port: " . ($config['port'] ?? 'N/A') . "\n";
    echo "Client: " . config('database.redis.client') . "\n";

    echo "\n--- Connectivity Test ---\n";
    $host = $config['host'] ?? '127.0.0.1';
    $port = $config['port'] ?? 6379;
    
    echo "Attempting raw socket connect to $host:$port...\n";
    $fp = @fsockopen($host, $port, $errno, $errstr, 2);
    if (!$fp) {
        echo "FAILURE: $errno - $errstr\n";
    } else {
        echo "SUCCESS: Connected via fsockopen\n";
        fclose($fp);
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
