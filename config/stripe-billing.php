<?php

return [
    'models' => [
        'owner' => 'App\User',
        'subscription' => \TMyers\StripeBilling\Models\Subscription::class,
        'pricing_plan' => \TMyers\StripeBilling\Models\PricingPlan::class,
        'plan' => \TMyers\StripeBilling\Models\Plan::class,
        'card' => \TMyers\StripeBilling\Models\Card::class,
    ],

    'tables' => [
        'owner' => 'users',
        'subscriptions' => 'subscriptions',
        'pricing_plans' => 'pricing_plans',
        'plans' => 'plans',
        'cards' => 'cards',
    ],
];