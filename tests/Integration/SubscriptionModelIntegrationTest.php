<?php

namespace TMyers\StripeBilling\Tests\Integration;


use Carbon\Carbon;
use TMyers\StripeBilling\Tests\TestCase;

class SubscriptionModelIntegrationTest extends TestCase
{
    public function setUp()
    {
        if (!env('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Integration tests are being skipped. See phpunit.xml');
        }

        parent::setUp();

        Carbon::setTestNow(now()->addMinutes(5));
    }

    protected function tearDown()
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

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
    }

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
        $this->assertFalse($user->fresh()->isSubscribedTo($monthlyPlan));
        $this->assertFalse($subscription->isFor($monthlyPlan));
        $this->assertFalse($subscription->isActive());
    }
}