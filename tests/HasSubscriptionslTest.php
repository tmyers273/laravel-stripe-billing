<?php

namespace TMyers\StripeBilling\Tests;


use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class HashSubscriptionsTest extends TestCase
{
    /** @test */
    public function user_can_subscribe_to_regular_monthly_plan()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPlan();
        $teamPlan = $this->createTeamMonthlyPlan();

        $subscription = $user->subscribeTo($monthlyPlan);

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('subscriptions', [
            'user_id'=> $user->id,
            'plan_id' => $monthlyPlan->id,
        ]);

        tap($user->fresh(), function(User $user) use ($monthlyPlan, $teamPlan) {
            $this->assertTrue($user->isSubscribedTo($monthlyPlan));
            $this->assertFalse($user->isSubscribedTo($teamPlan));
        });
    }

    /** @test */
    public function user_can_subscribe_to_basic__type_monthly_plan()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicType = $this->createBasicPlanType();
        $basicPlan = $this->createBasicMonthlyPlan($basicType);

        $teamType = $this->createTeamPlanType();
        $teamPlan = $this->createTeamMonthlyPlan($teamType);

        $subscription = $user->subscribeTo($basicPlan);

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('subscriptions', [
            'user_id'=> $user->id,
            'plan_id' => $basicPlan->id,
        ]);

        tap($user->fresh(), function(User $user) use ($basicType, $basicPlan, $teamType, $teamPlan) {
            $this->assertTrue($user->isSubscribedTo($basicPlan));
            $this->assertTrue($user->isSubscribedTo($basicType));
            $this->assertFalse($user->isSubscribedTo($teamPlan));
            $this->assertFalse($user->isSubscribedTo($teamType));
        });
    }
}