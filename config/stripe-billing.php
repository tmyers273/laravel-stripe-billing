<?php

return [
    'models' => [
        'owner' => 'App\User',
        'subscription' => \TMyers\StripeBilling\Models\Subscription::class,
        'plan' => \TMyers\StripeBilling\Models\Plan::class,
        'plan_type' => \TMyers\StripeBilling\Models\PlanType::class,
        'card' => \TMyers\StripeBilling\Models\Card::class,
    ],

    'tables' => [
        'owner' => 'users',
        'subscriptions' => 'subscriptions',
        'plans' => 'plans',
        'plan_types' => 'plan_types',
        'cards' => 'cards',
    ],
];