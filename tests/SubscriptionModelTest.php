<?php

namespace TMyers\StripeBilling\Tests;


use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class SubscriptionModelTest extends TestCase
{
    /** @test */
    public function it_belongs_to_user()
    {
        $user = $this->createUser();
        $plan = $this->createBasicMonthlyPlan();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertInstanceOf(Plan::class, $subscription->plan);
    }
}