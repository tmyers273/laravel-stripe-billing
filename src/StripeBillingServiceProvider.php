<?php

namespace TMyers\StripeBilling;


use Illuminate\Support\ServiceProvider;

class StripeBillingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/stripe-billing.php' => config_path('stripe-billing.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/stripe-billing.php',
            'stripe-billing'
        );

        if ( ! class_exists('CreatePlansTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_plans_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_1_create_plans_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreateSubscriptionsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_subscriptions_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_2_create_subscriptions_table.php'),
            ], 'migrations');
        }
    }
}