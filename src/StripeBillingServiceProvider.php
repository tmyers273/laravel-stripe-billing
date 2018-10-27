<?php

namespace TMyers\StripeBilling;


use Illuminate\Support\ServiceProvider;
use TMyers\StripeBilling\Gateways\StripeChargeGateway;
use TMyers\StripeBilling\Gateways\StripeCustomerGateway;
use TMyers\StripeBilling\Gateways\StripeSubscriptionGateway;

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

        if ( ! class_exists('AddStripeBillingColumnsToOwnerTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/add_stripe_billing_columns_to_owner_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_1_add_stripe_billing_columns_to_owner_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreatePlansTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_plans_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_2_create_plans_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreatePricingPlansTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_pricing_plans_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_3_create_pricing_plans_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreateSubscriptionsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_subscriptions_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_4_create_subscriptions_table.php'),
            ], 'migrations');
        }

        $this->app->bind('stripe-customer-gateway', function() {
            return new StripeCustomerGateway();
        });

        $this->app->bind('stripe-subscription-gateway', function() {
            return new StripeSubscriptionGateway();
        });

        $this->app->bind('stripe-charge-gateway', function() {
            return new StripeChargeGateway();
        });
    }
}