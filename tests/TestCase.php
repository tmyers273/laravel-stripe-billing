<?php

namespace TMyers\StripeBilling\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\PlanType;
use TMyers\StripeBilling\StripeBillingServiceProvider;
use TMyers\StripeBilling\Tests\Helpers\PlanFactory;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

abstract class TestCase extends OrchestraTestCase
{
    use PlanFactory;

    public function setUp()
    {
        parent::setUp();

        config()->set('stripe-billing.models.user', User::class);

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
        if (getenv('USE_MYSQL') !== 'yes') {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }
    }

    protected function setUpDatabase($app)
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        include_once __DIR__.'/../database/migrations/create_plan_types_table.php';
        include_once __DIR__.'/../database/migrations/create_plans_table.php';
        include_once __DIR__.'/../database/migrations/create_subscriptions_table.php';

        (new \CreatePlanTypesTable())->up();
        (new \CreatePlansTable())->up();
        (new \CreateSubscriptionsTable())->up();
    }

    /**
     * @param array $overrides
     * @return User
     */
    protected function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Denis',
            'email' => 'denis.mitr@gmail.com'
        ], $overrides));
    }

    protected function getTestToken(): string
    {
        return \Stripe\Token::create([
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 5,
                'exp_year' => 2020,
                'cvc' => '123',
            ],
        ], ['api_key' => getenv('STRIPE_SECRET')])->id;
    }
}