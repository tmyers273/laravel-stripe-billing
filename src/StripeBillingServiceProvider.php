<?php

namespace TMyers\StripeBilling;


use Illuminate\Support\ServiceProvider;
use TMyers\StripeBilling\Gateways\StripeCustomerGateway;

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

        if ( ! class_exists('AddStripeIdToOwnerTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/add_stripe_id_to_owner_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_1_add_stripe_id_to_owner_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreatePlanTypesTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_plan_types_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_2_create_plan_types_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreatePlansTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_plans_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_3_create_plans_table.php'),
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
    }
}