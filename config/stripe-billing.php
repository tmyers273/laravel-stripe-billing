<?php

return [
    'models' => [
        'owner' => 'App\User',
        'subscription' => \TMyers\StripeBilling\Models\Subscription::class,
        'prices' => \TMyers\StripeBilling\Models\StripePrice::class,
        'product' => \TMyers\StripeBilling\Models\StripeProduct::class,
        'card' => \TMyers\StripeBilling\Models\Card::class,
    ],

    'tables' => [
        'owner' => 'users',
        'subscriptions' => 'subscriptions',
        'prices' => 'stripe_prices',
        'products' => 'stripe_products',
        'cards' => 'cards',
    ],

    'unique_active_subscription' => false,
];
