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
        $monthlyPlan = $this->createMonthlyPricingPlan();
        $teamMonthlyPricingPlan = $this->createTeamMonthlyPricingPlan();

        $subscription = $user->subscribeTo($monthlyPlan, $this->createTestToken());

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $monthlyPlan->id,
            'type' => 'default',
            'ends_at' => null,
        ]);

        tap($user->fresh(), function(User $user) use ($monthlyPlan, $teamMonthlyPricingPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPlan));
            $this->assertFalse($user->isSubscribedTo($teamMonthlyPricingPlan));

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
        $basicMonthlyPricingPlan = $this->createBasicMonthlyPricingPlan($basicPlan);

        $teamPlan = $this->createTeamPlan();
        $teamMonthlyPricingPlan = $this->createTeamMonthlyPricingPlan($teamPlan);

        $subscription = $user->subscribeTo($basicMonthlyPricingPlan, $this->createTestToken());

        $this->assertInstanceOf(Subscription::class, $subscription);

        // expect subscription to be created
        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $basicMonthlyPricingPlan->id,
            'type' => 'basic',
            'trial_ends_at' => now()->addDays(11)
        ]);

        tap($user->fresh(), function(User $user) use ($basicPlan, $basicMonthlyPricingPlan, $teamPlan, $teamMonthlyPricingPlan) {
            // expect to be subscribed to basic plan
            $this->assertTrue($user->isSubscribedTo($basicMonthlyPricingPlan));
            $this->assertTrue($user->isSubscribedTo($basicPlan));
            $this->assertTrue($user->isSubscribedTo('basic'));

            // expect not to be subscribed to other plans
            $this->assertFalse($user->isSubscribedTo($teamMonthlyPricingPlan));
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
        $basicMonthlyPricingPlan = $this->createBasicMonthlyPricingPlan($basicPlan);

        $teamPlan = $this->createTeamPlan();
        $teamMonthlyPricingPlan = $this->createTeamMonthlyPricingPlan($teamPlan);

        $basicMonthlySubscription = $user->subscribeTo($basicMonthlyPricingPlan, $this->createTestToken());

        $basicMonthlySubscription->cancelNow();

        $this->assertFalse($basicMonthlySubscription->isActive());
        $this->assertCount(0, $user->fresh()->activeSubscriptions);
        $this->assertFalse($user->fresh()->hasActiveSubscriptions());

        $teamSubscription = $user->subscribeTo($teamMonthlyPricingPlan);

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
        $basicMonthlyPricingPlan = $this->createBasicMonthlyPricingPlan($basicPlan);

        $teamPlan = $this->createTeamPlan();
        $teamMonthlyPricingPlan = $this->createTeamMonthlyPricingPlan($teamPlan);

        $basicMonthlySubscription = $user->subscribeTo($basicMonthlyPricingPlan, $this->createTestToken());

        $basicMonthlySubscription->cancelNow();

        $this->assertFalse($basicMonthlySubscription->isActive());
        $this->assertCount(0, $user->fresh()->activeSubscriptions);
        $this->assertFalse($user->fresh()->hasActiveSubscriptions());

        $teamSubscription = $user->subscribeTo($teamMonthlyPricingPlan);

        $this->assertTrue($teamSubscription->isActive());
        $this->assertCount(1, $user->fresh()->activeSubscriptions);
        $this->assertTrue($user->fresh()->hasActiveSubscriptions());

        $this->assertEquals(1, $user->retrieveStripeCustomer()->subscriptions->total_count);
    }
}
