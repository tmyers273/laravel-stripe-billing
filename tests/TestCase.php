<?php

namespace TMyers\StripeBilling\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\StripeBillingServiceProvider;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class TestCase extends OrchestraTestCase
{
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

        include_once __DIR__.'/../database/migrations/create_plans_table.php';
        include_once __DIR__.'/../database/migrations/create_subscriptions_table.php';

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

    /**
     * @param array $overrides
     * @return Plan
     */
protected function createBasicMonthlyPlan(array $overrides = []): Plan
{
    return Plan::create(array_merge([
        'name' => 'Basic monthly plan',
        'code' => 'basic-monthly',
        'interval' => 'month',
        'stripe_plan_id' => 'basic_monthly',
        'price' => 1500,
    ], $overrides));
}

    /**
     * @param array $overrides
     * @return Plan
     */
    protected function createTeamMonthlyPlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Team monthly plan for 10 users',
            'code' => 'team-monthly-10',
            'interval' => 'month',
            'stripe_plan_id' => 'team_monthly_10',
            'price' => 4500,
            'teams_enabled' => true,
            'team_users_limit' => 10,
        ], $overrides));
    }
}