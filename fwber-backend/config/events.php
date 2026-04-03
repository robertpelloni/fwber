<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Event Bus Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "redis", "kafka", "log"
    |
    */
    'default' => env('EVENT_BUS_DRIVER', 'redis'),

    'drivers' => [
        'redis' => [
            'stream' => 'fwber:events',
            'retention' => 100000,
        ],
        'kafka' => [
            'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
            'topic' => 'fwber.events',
        ],
        'log' => [
            'channel' => 'stack',
        ],
    ],
];
