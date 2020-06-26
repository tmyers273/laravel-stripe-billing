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
        'pricing_plans' => 'pricing_plans',
        'plans' => 'plans',
        'cards' => 'cards',
    ],

    'unique_active_subscription' => false,
];
