<?php

namespace TMyers\StripeBilling\Tests;

use Carbon\Carbon;
use Stripe\Card;
use Stripe\Customer;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\OnlyOneActiveSubscriptionIsAllowed;
use TMyers\StripeBilling\Exceptions\SubscriptionNotFound;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\Models\StripePrice;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Helpers\StripeObjectsFactory;
use TMyers\StripeBilling\Tests\Helpers\SubscriptionFactory;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class HasSubscriptionsTest extends TestCase
{
    public function setUp(): void {
        parent::setUp();

        Carbon::setTestNow(now());
    }

    protected function tearDown(): void {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function user_can_subscribe_to_regular_monthly_plan_with_credit_card_token()
    {
        Carbon::setTestNow(now());

        // Given we have a user and two simple plans
        $user = $this->createUser();
        $monthlyPrice = $this->createMonthlyPrice();
        $teamPlan = $this->createTeamMonthlyPrice();

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
                'exp_month' => 8,
                'exp_year' => 2019,
            ]);

        StripeSubscription::shouldReceive('create')
            ->once()
            ->with($customer, [
                'items' => [
                    ['price' => $monthlyPrice->stripe_price_id],
                ],
                'trial_end' => now()->getTimestamp() + 86400, // 1 trial day
            ])
            ->andReturn($this->createSubscriptionObject('new-subscription-id'));

        // Do subscribe to monthly plan
        $subscription = $user->subscribeTo($monthlyPrice, 1, $token);

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'stripe_id' => 'new-customer-id',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'price_id' => $monthlyPrice->id,
            'stripe_subscription_id' => 'new-subscription-id',
        ]);

        tap($user->fresh(), function(User $user) use ($monthlyPrice, $teamPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPrice));
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
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);

        $teamType = $this->createTeamPlan();
        $teamPlan = $this->createTeamMonthlyPrice($teamType);

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
                'exp_month' => 8,
                'exp_year' => 2019,
            ]);

        StripeSubscription::shouldReceive('create')
            ->once()
            ->with($customer, [
                'items' => [
                    ['price' => $basicMonthlyPrice->stripe_price_id],
                ],
                'trial_end' => now()->addDays(11)->getTimestamp(),
            ])
            ->andReturn($this->createSubscriptionObject('new-subscription-id'));

        $subscription = $user->subscribeTo($basicMonthlyPrice, 11, $token);

        $this->assertInstanceOf(Subscription::class, $subscription);

        // expect subscription to be created
        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> (string) $user->id,
            'price_id' => (string) $basicMonthlyPrice->id,
            'type' => 'basic',
            'stripe_subscription_id' => 'new-subscription-id',
            'trial_ends_at' => now()->addDays(11)->toDateTimeString(),
        ]);

        tap($user->fresh(), function(User $user) use ($subscription, $basicPlan, $basicMonthlyPrice, $teamType, $teamPlan) {
            // expect to be subscribed to basic plan
            $this->assertTrue($user->isSubscribedTo($basicMonthlyPrice));
            $this->assertTrue($user->isSubscribedTo($basicPlan));
            $this->assertTrue($user->isSubscribedTo('basic'));

            $this->assertTrue($user->getSubscriptionFor($basicMonthlyPrice)->is($subscription));
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

    /** @test
     * @throws SubscriptionNotFound
     */
    public function it_can_get_subscription_for_user()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);
        $basicSubscription = $this->createActiveSubscription($user, $basicMonthlyPrice);

        $teamPlan = $this->createTeamPlan();
        $teamPrice = $this->createTeamMonthlyPrice($teamPlan);
        $teamSubscription = $this->createActiveSubscription($user, $teamPrice);

        // Expect correct subscription to be found
        $this->assertTrue($teamSubscription->is($user->getSubscriptionFor($teamPrice)));
        $this->assertTrue($basicSubscription->is($user->getSubscriptionFor($basicMonthlyPrice)));

        // by code name
        $this->assertTrue($teamSubscription->is($user->getSubscriptionFor($teamPrice->name)));
        $this->assertTrue($basicSubscription->is($user->getSubscriptionFor($basicMonthlyPrice->name)));

        // Expect subscription to e retrieved by pricing plan model
        $this->assertTrue($user->getSubscriptionFor($basicMonthlyPrice)->isActive());
        $this->assertTrue($user->getSubscriptionFor($basicMonthlyPrice)->is($basicSubscription));
        $this->assertTrue($user->getSubscriptionFor($teamPrice)->isActive());
        $this->assertTrue($user->getSubscriptionFor($teamPrice)->is($teamSubscription));

        // Expect subscription to e retrieved by pricing plan name
        $this->assertTrue($user->getSubscriptionFor($basicMonthlyPrice->name)->isActive());
        $this->assertTrue($user->getSubscriptionFor($basicMonthlyPrice->name)->is($basicSubscription));
        $this->assertTrue($user->getSubscriptionFor($teamPrice->name)->isActive());
        $this->assertTrue($user->getSubscriptionFor($teamPrice->name)->is($teamSubscription));

        // Expect first subscription to be the basic subscription
        $this->assertTrue($user->getFirstActiveSubscription()->isActive());
        $this->assertTrue($user->getFirstActiveSubscription()->is($basicSubscription));
    }

    /** @test */
    public function it_will_throw_if_subscription_not_found()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);
        $basicSubscription = $this->createActiveSubscription($user, $basicMonthlyPrice);

        $teamType = $this->createTeamPlan();
        $teamPlan = $this->createTeamMonthlyPrice($teamType);
        $teamSubscription = $this->createActiveSubscription($user, $teamPlan);

        $monthlyPrice = $this->createMonthlyPrice();

        $this->expectException(SubscriptionNotFound::class);

        $user->getSubscriptionFor($monthlyPrice);
    }

    /**
     * @test
     * @throws AlreadySubscribed
     */
    public function user_cannot_subscribe_to_the_same_pricing_plan_twice()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);
        $this->createActiveSubscription($user, $basicMonthlyPrice);

        $this->expectException(AlreadySubscribed::class);

        $user->subscribeTo($basicMonthlyPrice, 1);
    }

    /**
     * @test
     * @throws AlreadySubscribed
     * @throws \TMyers\StripeBilling\Exceptions\OnlyOneActiveSubscriptionIsAllowed
     */
    public function user_cannot_subscribe_to_the_same_plan_twice()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);
        $basicYearlyPrice = $this->createBasicYearlyPrice($basicPlan);
        $this->createActiveSubscription($user, $basicMonthlyPrice);

        $this->expectException(AlreadySubscribed::class);

        $user->subscribeTo($basicYearlyPrice, 1);
    }

    /**
     * @test
     */
    public function user_can_be_forced_to_have_only_one_active_subscription()
    {
        // Given only one subscription is allowed per user
        config()->set('stripe-billing.unique_active_subscription', true);

        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);
        $this->createActiveSubscription($user, $basicMonthlyPrice);

        $teamPlan = $this->createTeamPlan();
        $teamPrice = $this->createTeamMonthlyPrice($teamPlan);

        // Expect exception
        $this->expectException(OnlyOneActiveSubscriptionIsAllowed::class);

        // Do try to subscribe to another plan
        $user->subscribeTo($teamPrice, 1);
    }
}
