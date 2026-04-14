<?php

// Detroit Center: 42.3314, -83.0458
$baseUrl = 'https://api.fwber.me/api';
$token = 'YOUR_SESSION_TOKEN'; // Will be replaced by tinker-fetched token

echo "--- FWBER DETROIT SWARM SIMULATOR ---\n";
echo "Simulating 50 users moving in the Detroit Grid...\n";

$concurrency = 10;
$totalRequests = 50;
$successCount = 0;
$failCount = 0;
$totalTime = 0;

for ($i = 0; $i < $totalRequests; $i++) {
    $lat = 42.3314 + (mt_rand(-100, 100) / 10000);
    $lng = -83.0458 + (mt_rand(-100, 100) / 10000);

    $start = microtime(true);

    $ch = curl_init("$baseUrl/location");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'latitude' => $lat,
        'longitude' => $lng,
        'accuracy' => 10,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer '.$token,
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass for local resolution issues

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $elapsed = microtime(true) - $start;
    $totalTime += $elapsed;

    if ($httpCode >= 200 && $httpCode < 300) {
        $successCount++;
        echo "✅ Request $i: {$httpCode} ({$elapsed}s)\n";
    } else {
        $failCount++;
        echo "❌ Request $i: {$httpCode} - Response: $response\n";
    }

    curl_close($ch);
}

$avg = $totalTime / $totalRequests;
echo "\n--- RESULTS ---\n";
echo "Success: $successCount\n";
echo "Failures: $failCount\n";
echo 'Avg Latency: '.round($avg, 4)."s\n";
echo 'Total Time: '.round($totalTime, 2)."s\n";
