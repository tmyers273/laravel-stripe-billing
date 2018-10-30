<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 21.10.2018
 * Time: 17:41
 */

namespace TMyers\StripeBilling\Tests;


use TMyers\StripeBilling\Models\Plan;

class PlanModelTest extends TestCase
{
    /** @test */
    public function it_can_have_multiple_pricing_plans()
    {
        // Given
        $basic = $this->createBasicPlan();

        $yearlyPlan = $this->createPricingPlan($basic, [
            'name' => 'basic_yearly',
            'description' => 'Basic yearly plan',
            'price' => 9900,
            'interval' => '1 year',
            'active' => true,
        ]);

        $montlyPlan = $this->createPricingPlan($basic, [
            'name' => 'basic_monthly',
            'description' => 'Basic monthly plan',
            'price' => 2500,
            'interval' => '1 month',
            'active' => true,
        ]);

        $pricing = $basic->pricingPlans;

        $this->assertCount(2, $pricing);

        $this->assertTrue($pricing[0]->is($montlyPlan));
        $this->assertEquals('basic', $pricing[0]->getType());
        $this->assertInstanceOf(Plan::class, $pricing[0]->plan);

        $this->assertTrue($pricing[1]->is($yearlyPlan));
        $this->assertEquals('basic', $pricing[1]->getType());
        $this->assertInstanceOf(Plan::class, $pricing[1]->plan);

        $this->assertFalse($basic->isFree());
    }

    /** @test */
    public function it_can_view_all_underlying_subscriptions()
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        // Given
        $basic = $this->createBasicPlan();

        $yearlyPlan = $this->createPricingPlan($basic, [
            'name' => 'basic_yearly',
            'description' => 'Basic yearly plan',
            'price' => 9900,
            'interval' => '1 year',
            'active' => true,
        ]);

        $montlyPlan = $this->createPricingPlan($basic, [
            'name' => 'basic_monthly',
            'description' => 'Basic monthly plan',
            'price' => 2500,
            'interval' => '1 month',
            'active' => true,
        ]);

        $subscriptionA = $this->createActiveSubscription($userA, $montlyPlan);
        $subscriptionB = $this->createActiveSubscription($userB, $montlyPlan);
        $subscriptionC = $this->createActiveSubscription($userA, $yearlyPlan);

        $basicSubscriptions = $basic->subscriptions;

        $this->assertCount(3, $basicSubscriptions);
        $this->assertTrue($basicSubscriptions[0]->is($subscriptionA));
        $this->assertTrue($basicSubscriptions[1]->is($subscriptionB));
        $this->assertTrue($basicSubscriptions[2]->is($subscriptionC));
    }
}