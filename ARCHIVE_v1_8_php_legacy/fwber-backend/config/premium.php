<?php

return [
    'plans' => [
        'gold_monthly' => [
            'name' => 'gold',
            'display_name' => 'Gold Monthly',
            'description' => env('PREMIUM_GOLD_MONTHLY_DESCRIPTION', 'Premium Subscription'),
            'price_usd' => (float) env('PREMIUM_GOLD_MONTHLY_PRICE_USD', 19.99),
            'currency' => env('PREMIUM_GOLD_MONTHLY_CURRENCY', 'USD'),
            'duration_days' => (int) env('PREMIUM_GOLD_MONTHLY_DURATION_DAYS', 30),
            'token_cost' => (int) env('PREMIUM_GOLD_MONTHLY_TOKEN_COST', 200),
            'stripe_price' => env('PREMIUM_GOLD_MONTHLY_STRIPE_PRICE', 'price_premium_monthly'),
            'interval' => 'month',
        ],
    ],
];
