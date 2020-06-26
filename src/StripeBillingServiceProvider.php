<?php

namespace TMyers\StripeBilling;


use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use TMyers\StripeBilling\Gateways\StripeChargeGateway;
use TMyers\StripeBilling\Gateways\StripeCustomerGateway;
use TMyers\StripeBilling\Gateways\StripeSubscriptionGateway;

class StripeBillingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfigPublisher();
        $this->registerMigrationPublisher();

        $this->registerBladeDirectives();

        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    protected function registerConfigPublisher() {
        $this->publishes([
            __DIR__.'/../config/stripe-billing.php' => config_path('stripe-billing.php'),
        ], 'config');
    }

    protected function registerMigrationPublisher() {
        if ( ! class_exists('AddStripeBillingColumnsToOwnerTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/add_stripe_billing_columns_to_owner_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_add_stripe_billing_columns_to_owner_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreateProductsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_stripe_products_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_create_stripe_products_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreateStripePricesTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_stripe_prices_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_create_stripe_prices_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreateStripeSubscriptionsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_subscriptions_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_create_subscriptions_table.php'),
            ], 'migrations');
        }

        if ( ! class_exists('CreateCardsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_cards_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_create_cards_table.php'),
            ], 'migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/stripe-billing.php',
            'stripe-billing'
        );

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

    protected function registerBladeDirectives()
    {
        Blade::directive('subscribed', function() {
            return "<?php if(auth()->check() && auth()->user()->hasActiveSubscriptions()): ?>";
        });

        Blade::directive('endsubscribed', function() {
            return "<?php endif; ?>";
        });

        Blade::directive('unless_subscribed', function() {
            return "<?php if(auth()->check() && !auth()->user()->hasActiveSubscriptions()): ?>";
        });

        Blade::directive('endunless_subscribed', function() {
            return "<?php endif; ?>";
        });
    }
}
