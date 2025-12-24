<?php
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyJodHRwczovL2Z3YmVyLm1lL3VzZXJzLzEiLCJodHRwczovL2Z3YmVyLm1lL3VzZXJzLzEvKiIsImh0dHBzOi8vZndiZXIubWUvYnVsbGV0aW4tYm9hcmRzLyoiLCJodHRwczovL2Z3YmVyLm1lL3B1YmxpYy8qIiwiL3VzZXJzLzEiLCIvdXNlcnMvMS8qIiwiL2J1bGxldGluLWJvYXJkcy8qIiwiL3B1YmxpYy8qIl19LCJzdWIiOiIxIiwiZXhwIjoxNzY2NTg1NDg3LCJpYXQiOjE3NjY1ODE4ODd9.itYSTZu-cpMmTtu1F9KCv13ZulDhez8ik75HgVknWPk';

$keys = [
    'NEW_KEY' => 'fwber_mercure_secret_key_2025_secure_and_long',
    'OLD_KEY_BASE64' => 'uWnEOi51TibZqRn3YbRMvu0XbZwWp42X6z6s0aZMcAw=',
    'OLD_KEY_BINARY' => base64_decode('uWnEOi51TibZqRn3YbRMvu0XbZwWp42X6z6s0aZMcAw='),
];

foreach ($keys as $name => $key) {
    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        echo "MATCH FOUND: Token was signed with $name\n";
        exit(0);
    } catch (Exception $e) {
        echo "Failed with $name: " . $e->getMessage() . "\n";
    }
}

echo "No match found.\n";
