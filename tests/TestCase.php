<?php

namespace TMyers\StripeBilling\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TMyers\StripeBilling\Models\Card;
use TMyers\StripeBilling\Models\Price;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\StripeBillingServiceProvider;
use TMyers\StripeBilling\Tests\Helpers\ProductFactory;
use TMyers\StripeBilling\Tests\Helpers\StripeObjectsFactory;
use TMyers\StripeBilling\Tests\Helpers\SubscriptionFactory;
use TMyers\StripeBilling\Tests\Helpers\UserAndCardFactory;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

abstract class TestCase extends OrchestraTestCase
{
    use UserAndCardFactory, ProductFactory, SubscriptionFactory, StripeObjectsFactory;

    public function setUp(): void {
        parent::setUp();

        config()->set('stripe-billing.models.owner', User::class);

        $this->setUpDatabase($this->app);
    }

    /**
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            StripeBillingServiceProvider::class
        ];
    }

    protected function getEnvironmentSetup($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase($app)
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        include_once __DIR__ . '/../database/migrations/add_stripe_billing_columns_to_owner_table.php';
        include_once __DIR__ . '/../database/migrations/create_products_table.php';
        include_once __DIR__ . '/../database/migrations/create_prices_table.php';
        include_once __DIR__.'/../database/migrations/create_subscriptions_table.php';
        include_once __DIR__.'/../database/migrations/create_cards_table.php';

        (new \AddStripeBillingColumnsToOwnerTable())->up();
        (new \CreatePlansTable())->up();
        (new \CreatePricingPlansTable())->up();
        (new \CreateSubscriptionsTable())->up();
        (new \CreateCardsTable())->up();
    }
}
