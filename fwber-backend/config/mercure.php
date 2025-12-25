<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mercure Hub URL
    |--------------------------------------------------------------------------
    |
    | The URL of the Mercure hub. This URL will be used by the client to
    | subscribe to updates.
    |
    */

    'public_url' => env('MERCURE_PUBLIC_URL', 'http://localhost:3001/.well-known/mercure'),

    /*
    |--------------------------------------------------------------------------
    | Mercure Internal Hub URL
    |--------------------------------------------------------------------------
    |
    | The URL of the Mercure hub used by the server to publish updates.
    | This URL is not exposed to the client.
    |
    */

    'internal_url' => env('MERCURE_INTERNAL_URL', 'http://mercure/.well-known/mercure'),

    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign the JWT. This key must be shared with the
    | Mercure hub.
    |
    */

    'jwt_secret' => env('MERCURE_JWT_SECRET'), // Legacy
    'publisher_jwt_key' => env('MERCURE_PUBLISHER_JWT_KEY'),
    'subscriber_jwt_key' => env('MERCURE_SUBSCRIBER_JWT_KEY'),

];
