<?php

namespace TMyers\StripeBilling\Tests\Integration;


use Illuminate\Support\Carbon;
use Stripe\Coupon;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\StripeBilling;
use TMyers\StripeBilling\Tests\Stubs\Models\User;
use TMyers\StripeBilling\Tests\TestCase;

class HasSubscriptionsIntegrationTest extends TestCase
{
    public function setUp(): void {
        if (!env('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Integration tests are being skipped. See phpunit.xml');
        }

        parent::setUp();

        Carbon::setTestNow(now()->addMinutes(5));
    }

    protected function tearDown(): void {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function user_can_subscribe_to_regular_monthly_plan()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPrice();
        $teamMonthlyPrice = $this->createTeamMonthlyPrice();

        $subscription = $user->subscribeTo($monthlyPlan, 1, $this->createTestToken());

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'price_id' => $monthlyPlan->id,
            'type' => 'default',
            'ends_at' => null,
        ]);

        tap($user->fresh(), function(User $user) use ($monthlyPlan, $teamMonthlyPrice) {
            $this->assertTrue($user->isSubscribedTo($monthlyPlan));
            $this->assertFalse($user->isSubscribedTo($teamMonthlyPrice));

            // Expect new card to be created
            $defaultCard = $user->defaultCard;

            $this->assertNotNull($defaultCard->stripe_card_id);

            $this->assertDatabaseHas('cards', [
                'id' => $defaultCard->id,
                'owner_id'=> $user->id,
                'last_4' => 4242,
                'brand' => 'Visa',
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

        $teamPlan = $this->createTeamPlan();
        $teamMonthlyPrice = $this->createTeamMonthlyPrice($teamPlan);

        $subscription = $user->subscribeTo($basicMonthlyPrice, 1, $this->createTestToken());

        $this->assertInstanceOf(Subscription::class, $subscription);

        // expect subscription to be created
        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'price_id' => $basicMonthlyPrice->id,
            'type' => 'basic',
            'trial_ends_at' => now()->addDays(11)
        ]);

        tap($user->fresh(), function(User $user) use ($basicPlan, $basicMonthlyPrice, $teamPlan, $teamMonthlyPrice) {
            // expect to be subscribed to basic plan
            $this->assertTrue($user->isSubscribedTo($basicMonthlyPrice));
            $this->assertTrue($user->isSubscribedTo($basicPlan));
            $this->assertTrue($user->isSubscribedTo('basic'));

            // expect not to be subscribed to other plans
            $this->assertFalse($user->isSubscribedTo($teamMonthlyPrice));
            $this->assertFalse($user->isSubscribedTo($teamPlan));

            // expect new card to be created
            $defaultCard = $user->defaultCard;

            $this->assertNotNull($defaultCard->stripe_card_id);

            $this->assertDatabaseHas('cards', [
                'id' => $defaultCard->id,
                'owner_id'=> $user->id,
                'last_4' => 4242,
                'brand' => 'Visa',
            ]);
        });
    }

    /** @test */
    public function user_can_create_new_subscription_given_an_old_canceled_one()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);

        $teamPlan = $this->createTeamPlan();
        $teamMonthlyPrice = $this->createTeamMonthlyPrice($teamPlan);

        $basicMonthlySubscription = $user->subscribeTo($basicMonthlyPrice, 1, $this->createTestToken());

        $basicMonthlySubscription->cancelNow();

        $this->assertFalse($basicMonthlySubscription->isActive());
        $this->assertCount(0, $user->fresh()->activeSubscriptions);
        $this->assertFalse($user->fresh()->hasActiveSubscriptions());

        $teamSubscription = $user->subscribeTo($teamMonthlyPrice, 1);

        $this->assertTrue($teamSubscription->isActive());
        $this->assertCount(1, $user->fresh()->activeSubscriptions);
        $this->assertTrue($user->fresh()->hasActiveSubscriptions());
    }

    /** @test */
    public function user_can_create_new_subscription_given_an_old_canceled_one_and_unique_subscription_constraint()
    {
        // Given only one subscription is allowed per user
        config()->set('stripe-billing.unique_active_subscription', true);

        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicPlan();
        $basicMonthlyPrice = $this->createBasicMonthlyPrice($basicPlan);

        $teamPlan = $this->createTeamPlan();
        $teamMonthlyPrice = $this->createTeamMonthlyPrice($teamPlan);

        $basicMonthlySubscription = $user->subscribeTo($basicMonthlyPrice, 1, $this->createTestToken());

        $basicMonthlySubscription->cancelNow();

        $this->assertFalse($basicMonthlySubscription->isActive());
        $this->assertCount(0, $user->fresh()->activeSubscriptions);
        $this->assertFalse($user->fresh()->hasActiveSubscriptions());

        $teamSubscription = $user->subscribeTo($teamMonthlyPrice, 1);

        $this->assertTrue($teamSubscription->isActive());
        $this->assertCount(1, $user->fresh()->activeSubscriptions);
        $this->assertTrue($user->fresh()->hasActiveSubscriptions());

        $this->assertEquals(1, $user->retrieveStripeCustomer()->subscriptions->total_count);
    }
}
