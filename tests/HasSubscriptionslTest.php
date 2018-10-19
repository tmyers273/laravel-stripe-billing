<?php

namespace TMyers\StripeBilling\Tests;


use Carbon\Carbon;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class HasSubscriptionsTest extends TestCase
{
    /** @test */
    public function user_can_subscribe_to_regular_monthly_plan()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPlan();
        $teamPlan = $this->createTeamMonthlyPlan();

        $subscription = $user->subscribeTo($monthlyPlan, $this->getTestToken());

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'plan_id' => $monthlyPlan->id,
        ]);

        tap($user->fresh(), function(User $user) use ($monthlyPlan, $teamPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPlan));
            $this->assertFalse($user->isSubscribedTo($teamPlan));
        });
    }

    /** @test */
    public function user_can_subscribe_to_basic_type_monthly_plan()
    {
        Carbon::setTestNow(now());

        // Given we have a user and two plans
        $user = $this->createUser();
        $basicType = $this->createBasicPlanType();
        $basicPlan = $this->createBasicMonthlyPlan($basicType);

        $teamType = $this->createTeamPlanType();
        $teamPlan = $this->createTeamMonthlyPlan($teamType);

        $subscription = $user->subscribeTo($basicPlan, $this->getTestToken());

        $this->assertInstanceOf(Subscription::class, $subscription);

        // expect subscription to be created
        $this->assertDatabaseHas('subscriptions', [
            'owner_id'=> $user->id,
            'plan_id' => $basicPlan->id,
            'type' => 'basic',
            'trial_ends_at' => now()->addDays(11)
        ]);

        tap($user->fresh(), function(User $user) use ($basicType, $basicPlan, $teamType, $teamPlan) {
            // expect to be subscribed to basic plan
            $this->assertTrue($user->isSubscribedTo($basicPlan));
            $this->assertTrue($user->isSubscribedTo($basicType));
            $this->assertTrue($user->isSubscribedTo('basic'));

            // expect not to be subscribed to other plans
            $this->assertFalse($user->isSubscribedTo($teamPlan));
            $this->assertFalse($user->isSubscribedTo($teamType));
        });

        Carbon::setTestNow();
    }
}