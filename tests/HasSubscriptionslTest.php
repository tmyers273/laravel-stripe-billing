<?php

namespace TMyers\StripeBilling\Tests;


use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class HashSubscriptionsTest extends TestCase
{
    /** @test */
    public function user_can_subscribe_to_plan()
    {
        // Given we have a user and two plans
        $user = $this->createUser();
        $basicPlan = $this->createBasicMonthlyPlan();
        $teamPlan = $this->createTeamMonthlyPlan();

        $subscription = $user->subscribeTo($basicPlan);

        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertDatabaseHas('subscriptions', [
            'user_id'=> $user->id,
            'plan_id' => $basicPlan->id,
        ]);

        tap($user->fresh(), function(User $user) use ($basicPlan, $teamPlan) {
            $this->assertTrue($user->isSubscribedTo($basicPlan));
            $this->assertFalse($user->isSubscribedTo($teamPlan));
        });
    }
}