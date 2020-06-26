<?php

return [
    'models' => [
        'owner' => 'App\User',
        'subscription' => \TMyers\StripeBilling\Models\Subscription::class,
        'prices' => \TMyers\StripeBilling\Models\Price::class,
        'product' => \TMyers\StripeBilling\Models\Product::class,
        'card' => \TMyers\StripeBilling\Models\Card::class,
    ],

    'tables' => [
        'owner' => 'users',
        'subscriptions' => 'subscriptions',
        'pricing_plans' => 'pricing_plans',
        'plans' => 'plans',
        'cards' => 'cards',
    ],

    'unique_active_subscription' => false,
];
