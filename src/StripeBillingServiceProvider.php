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
    }
}