<?php

return [
'models' => [
    'user' => 'App\User',
    'subscription' => 'TMyers\StripeBilling\Models\Subscription',
    'plan' => 'TMyers\StripeBilling\Models\Plan',
],

'tables' => [
    'users' => 'users',
    'subscriptions' => 'subscriptions',
    'plan' => 'plans',
],
];