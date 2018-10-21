<?php

namespace TMyers\StripeBilling\Tests\Integration;


use Carbon\Carbon;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;
use TMyers\StripeBilling\Tests\TestCase;

class SubscriptionModelIntegrationTest extends TestCase
{
    public function setUp()
    {
        if (!env('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Integration tests are being skipped. See phpunit.xml');
        }

        parent::setUp();
    }

    /*
    |--------------------------------------------------------------------------
    | Plan swapping
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function subscription_plan_can_be_swapped()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();
        $teamPlan = $this->createTeamMonthlyPricingPlan();

        $subscription = $user->subscribeTo($monthlyPlan, $this->getTestToken());

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $monthlyPlan->id,
        ]);

        // Expect user to be subscribed to monthly plan
        $this->assertTrue($subscription->isFor($monthlyPlan));
        $this->assertTrue($user->fresh()->isSubscribedTo($monthlyPlan));

        $subscription->changeTo($teamPlan);

        // Expect user not to be subscribed to monthly plan anymore
        $this->assertFalse($subscription->isFor($monthlyPlan));
        $this->assertFalse($user->isSubscribedTo($monthlyPlan));

        // Expect user to be subscribed to team plan now
        $this->assertTrue($subscription->isFor($teamPlan));
        $this->assertTrue($user->fresh()->isSubscribedTo($teamPlan));

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $teamPlan->id,
        ]);

        $this->assertTrue($user->fresh()->hasActiveSubscriptions());
    }
    
    /*
    |--------------------------------------------------------------------------
    | Cancellation
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function subscription_can_be_canceled_now()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();

        $subscription = $user->subscribeTo($monthlyPlan, $this->getTestToken());

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $monthlyPlan->id,
        ]);

        // Expect user to be subscribed to monthly plan
        $this->assertTrue($subscription->isFor($monthlyPlan));
        $this->assertTrue($user->fresh()->isSubscribedTo($monthlyPlan));

        // Do cancel subscription immediately
        $subscription->cancelNow();

        // Expect user not to be subscribed to monthly plan anymore
        tap($user->fresh(), function(User $user) use ($monthlyPlan) {
            $this->assertFalse($user->isSubscribedTo($monthlyPlan));
            $this->assertFalse($user->hasActiveSubscriptions());
        });

        // Expect subscription not to be active anymore
        tap($subscription->fresh(), function(Subscription $subscription) use ($monthlyPlan) {
            $this->assertFalse($subscription->isFor($monthlyPlan));
            $this->assertFalse($subscription->isActive());
        });
    }

    /** @test */
    public function it_can_be_cancelled_at_period_end()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();

        $subscription = $user->subscribeTo($monthlyPlan, $this->getTestToken());

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $monthlyPlan->id,
        ]);

        // Expect user to be subscribed to monthly plan
        $this->assertTrue($subscription->isFor($monthlyPlan));
        $this->assertTrue($user->fresh()->isSubscribedTo($monthlyPlan));

        // Do cancel subscription immediately
        $subscription->cancelAtPeriodEnd();

        // Expect user to have active subscription until the end of the grace period
        tap($user->fresh(), function(User $user) use ($monthlyPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPlan));
            $this->assertTrue($user->hasActiveSubscriptions());
        });

        // Expect subscription to be on on grace period
        tap($subscription->fresh(), function(Subscription $subscription) use ($monthlyPlan) {
            $this->assertTrue($subscription->isFor($monthlyPlan));
            $this->assertTrue($subscription->isActive());
            $this->assertTrue($subscription->onGracePeriod());
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Resuming
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_be_canceled_and_resumed_while_on_grace_period()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();

        $subscription = $user->subscribeTo($monthlyPlan, $this->getTestToken());

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'pricing_plan_id' => $monthlyPlan->id,
        ]);

        // Expect user to be subscribed to monthly plan
        $this->assertTrue($subscription->isFor($monthlyPlan));
        $this->assertTrue($user->fresh()->isSubscribedTo($monthlyPlan));

        // Do cancel subscription immediately
        $subscription->cancelAtPeriodEnd();

        // Expect user to have active subscription until the end of the grace period
        tap($user->fresh(), function(User $user) use ($monthlyPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPlan));
            $this->assertTrue($user->hasActiveSubscriptions());
        });

        // Expect subscription to be on on grace period
        tap($subscription->fresh(), function(Subscription $subscription) use ($monthlyPlan) {
            $this->assertTrue($subscription->isFor($monthlyPlan));
            $this->assertTrue($subscription->isActive());
            $this->assertTrue($subscription->onGracePeriod());
        });

        $subscription->resume();

        // Expect user to have active subscription
        tap($user->fresh(), function(User $user) use ($monthlyPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPlan));
            $this->assertTrue($user->hasActiveSubscriptions());
        });

        // Expect subscription to be fully active again
        tap($subscription->fresh(), function(Subscription $subscription) use ($monthlyPlan) {
            $this->assertTrue($subscription->isFor($monthlyPlan));
            $this->assertTrue($subscription->isActive());
            $this->assertFalse($subscription->onGracePeriod());
        });
    }
}