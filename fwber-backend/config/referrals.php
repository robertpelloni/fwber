<?php

return [
    'frontend_url' => env('FRONTEND_URL', 'https://fwber.me'),

    'signup' => [
        'referrer_token_amount' => env('REFERRAL_SIGNUP_REFERRER_TOKEN_AMOUNT', 50),
        'referred_token_amount' => env('REFERRAL_SIGNUP_REFERRED_TOKEN_AMOUNT', 50),
    ],

    'premium' => [
        'level_1' => [
            'cash_usd' => env('REFERRAL_PREMIUM_LEVEL_1_CASH_USD', 2.00),
            'token_amount' => env('REFERRAL_PREMIUM_LEVEL_1_TOKEN_AMOUNT', 50),
        ],
        'level_2' => [
            'cash_usd' => env('REFERRAL_PREMIUM_LEVEL_2_CASH_USD', 0.50),
            'token_amount' => env('REFERRAL_PREMIUM_LEVEL_2_TOKEN_AMOUNT', 15),
        ],
    ],
];
