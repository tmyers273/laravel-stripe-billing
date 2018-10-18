<?php

return [
    'models' => [
        'user' => 'App\User',
        'subscription' => \TMyers\StripeBilling\Models\Subscription::class,
        'plan' => \TMyers\StripeBilling\Models\Plan::class,
        'plan_type' => \TMyers\StripeBilling\Models\PlanType::class,
    ],

    'tables' => [
        'users' => 'users',
        'subscriptions' => 'subscriptions',
        'plans' => 'plans',
        'plan_types' => 'plan_types',
    ],
];