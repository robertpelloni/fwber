<?php
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyJodHRwczovL2Z3YmVyLm1lL3VzZXJzLzEiLCJodHRwczovL2Z3YmVyLm1lL3VzZXJzLzEvKiIsImh0dHBzOi8vZndiZXIubWUvYnVsbGV0aW4tYm9hcmRzLyoiLCJodHRwczovL2Z3YmVyLm1lL3B1YmxpYy8qIiwiL3VzZXJzLzEiLCIvdXNlcnMvMS8qIiwiL2J1bGxldGluLWJvYXJkcy8qIiwiL3B1YmxpYy8qIl19LCJzdWIiOiIxIiwiZXhwIjoxNzY2NTg1OTM4LCJpYXQiOjE3NjY1ODIzMzh9.pbhWkgyI-kZ8so8WXDyH7lbmSQr7eUMjDOTkBgQQJNM';
$key = 'fwber_mercure_secret_key_2025_secure_and_long';

echo "Testing Token Verification...\n";
echo "Key: $key\n";

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    echo "SUCCESS: Token verified correctly!\n";
    print_r($decoded);
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
