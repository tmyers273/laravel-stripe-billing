<?php

namespace TMyers\StripeBilling\Tests;


use Carbon\Carbon;
use Stripe\Card;
use Stripe\Customer;
use TMyers\StripeBilling\Exceptions\SubscriptionNotFound;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\Models\PricingPlan;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Helpers\StripeObjectsFactory;
use TMyers\StripeBilling\Tests\Helpers\SubscriptionFactory;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class HasSubscriptionsTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(now());
    }

    protected function tearDown()
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function user_can_subscribe_to_regular_monthly_plan_with_credit_card_token()
    {
        // Given we have a user and two simple plans
        $user = $this->createUser();
        $monthlyPricingPlan = $this->createMonthlyPricingPlan();
        $teamPlan = $this->createTeamMonthlyPricingPlan();

        // Fake token
        $token = 'fake-credit-card-token';

        // Mock
        // 1. new customer must be creaded via a fake token, and email
        // 2. new subscription must be created for the customer mock
        StripeCustomer::shouldReceive('create')
            ->once()
            ->with($token, $user->email, [])
            ->andReturn($customer = $this->createCustomerObject('new-customer-id'));

        StripeCustomer::shouldReceive('parseDefaultCard')
            ->once()
            ->with($customer)
            ->andReturn([
                'stripe_card_id' => 'fake-card-id',
                'brand' => 'FakeBrand',
                'last_4' => '4242',
            ]);

        StripeSubscription::shouldReceive('create')
            ->once()
            ->with($customer, [
                'plan' => 'monthly',
                'trial_end' => now()->getTimestamp(),
            ])
            ->andReturn($this->createSubscriptionObject('new-subscription-id'));

        // Do subscribe to monthly plan
        $subscription = $user->subscribeTo($monthlyPricingPlan, $token);

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'stripe_id' => 'new-customer-id',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $monthlyPricingPlan->id,
            'stripe_subscription_id' => 'new-subscription-id',
        ]);

        tap($user->fresh(), function(User $user) use ($monthlyPricingPlan, $teamPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPricingPlan));
            $this->assertFalse($user->isSubscribedTo($teamPlan));

            // expect new card to be created
            $defaultCard = $user->defaultCard;

            $this->assertDatabaseHas('cards', [
                'id' => $defaultCard->id,
                'owner_id'=> $user->id,
                'stripe_card_id' => 'fake-card-id',
                'last_4' => 4242,
                'brand' => 'FakeBrand',
            ]);
        });
    }

    /** @test */
    public function user_can_subscribe_to_basic_type_monthly_plan()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPricingPlan = $this->createBasicMonthlyPricingPlan($basicPlan);

        $teamType = $this->createTeamPlan();
        $teamPlan = $this->createTeamMonthlyPricingPlan($teamType);

        // Fake token
        $token = 'fake-credit-card-token';

        // Mock
        // 1. new customer must be creaded via a fake token, and email
        // 2. new subscription must be created for the customer mock
        $customer = $this->createCustomerObject('new-customer-id');

        StripeCustomer::shouldReceive('create')
            ->once()
            ->with($token, $user->email, [])
            ->andReturn($customer);

        StripeCustomer::shouldReceive('parseDefaultCard')
            ->once()
            ->with($customer)
            ->andReturn([
                'stripe_card_id' => 'fake-card-id',
                'brand' => 'FakeBrand',
                'last_4' => '4242',
            ]);

        StripeSubscription::shouldReceive('create')
            ->once()
            ->with($customer, [
                'plan' => 'basic_monthly',
                'trial_end' => now()->addDays($basicMonthlyPricingPlan->trial_days)->getTimestamp(),
            ])
            ->andReturn($this->createSubscriptionObject('new-subscription-id'));

        $subscription = $user->subscribeTo($basicMonthlyPricingPlan, $token);

        $this->assertInstanceOf(Subscription::class, $subscription);

        // expect subscription to be created
        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $basicMonthlyPricingPlan->id,
            'type' => 'basic',
            'stripe_subscription_id' => 'new-subscription-id',
            'trial_ends_at' => now()->addDays(11)
        ]);

        tap($user->fresh(), function(User $user) use ($subscription, $basicPlan, $basicMonthlyPricingPlan, $teamType, $teamPlan) {
            // expect to be subscribed to basic plan
            $this->assertTrue($user->isSubscribedTo($basicMonthlyPricingPlan));
            $this->assertTrue($user->isSubscribedTo($basicPlan));
            $this->assertTrue($user->isSubscribedTo('basic'));

            $this->assertTrue($user->getSubscriptionFor($basicMonthlyPricingPlan)->is($subscription));
            $this->assertTrue($user->getFirstActiveSubscription()->is($subscription));

            // expect not to be subscribed to other plans
            $this->assertFalse($user->isSubscribedTo($teamPlan));
            $this->assertFalse($user->isSubscribedTo($teamType));

            // expect new card to be created
            $defaultCard = $user->defaultCard;

            $this->assertDatabaseHas('cards', [
                'id' => $defaultCard->id,
                'owner_id'=> $user->id,
                'stripe_card_id' => 'fake-card-id',
                'last_4' => 4242,
                'brand' => 'FakeBrand',
            ]);
        });
    }

    /** @test */
    public function it_can_get_subscription_for_user()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPricingPlan = $this->createBasicMonthlyPricingPlan($basicPlan);
        $basicSubscription = $this->createActiveSubscription($user, $basicMonthlyPricingPlan);

        $teamType = $this->createTeamPlan();
        $teamPlan = $this->createTeamMonthlyPricingPlan($teamType);
        $teamSubscription = $this->createActiveSubscription($user, $teamPlan);

        // Expect correct subscription to be found
        $this->assertTrue($teamSubscription->is($user->getSubscriptionFor($teamPlan)));
        $this->assertTrue($basicSubscription->is($user->getSubscriptionFor($basicMonthlyPricingPlan)));

        // by code name
        $this->assertTrue($teamSubscription->is($user->getSubscriptionFor($teamPlan->name)));
        $this->assertTrue($basicSubscription->is($user->getSubscriptionFor($basicMonthlyPricingPlan->name)));

        // chaining
        $this->assertTrue(
            $user->getSubscriptionFor($teamPlan->name)->isActive()
        );
    }

    /** @test */
    public function it_will_throw_if_subscription_not_found()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPricingPlan = $this->createBasicMonthlyPricingPlan($basicPlan);
        $basicSubscription = $this->createActiveSubscription($user, $basicMonthlyPricingPlan);

        $teamType = $this->createTeamPlan();
        $teamPlan = $this->createTeamMonthlyPricingPlan($teamType);
        $teamSubscription = $this->createActiveSubscription($user, $teamPlan);

        $monthlyPricingPlan = $this->createMonthlyPricingPlan();

        $this->expectException(SubscriptionNotFound::class);

        $user->getSubscriptionFor($monthlyPricingPlan);
    }
}