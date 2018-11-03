<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 21.10.2018
 * Time: 17:41
 */

namespace TMyers\StripeBilling\Tests;


use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\PricingPlan;

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
        $pricingPlans = $basic->pricingPlans;

        $this->assertCount(3, $basicSubscriptions);
        $this->assertCount(2, $pricingPlans);

        $this->assertTrue($basicSubscriptions[0]->is($subscriptionA));
        $this->assertTrue($basicSubscriptions[1]->is($subscriptionB));
        $this->assertTrue($basicSubscriptions[2]->is($subscriptionC));
    }

    /** @test */
    public function plans_can_be_sorted_by_weight()
    {
        $teamPlan = Plan::create([
            'description' => 'Team plan',
            'name' => 'team',
            'is_free' => false,
            'weight' => 10,
        ]);

        $basicPlan = Plan::create([
            'description' => 'Basic plan',
            'name' => 'basic',
            'is_free' => false,
            'weight' => 1,
        ]);

        $proPlan = Plan::create([
            'description' => 'Pro plan',
            'name' => 'pro',
            'is_free' => false,
            'weight' => 3,
        ]);

        $freePlan = Plan::create([
            'description' => 'Free plan',
            'name' => 'free',
            'is_free' => true,
            'weight' => 0,
        ]);

        $plans = Plan::weighted()->get();

        $this->assertCount(4, $plans);
        $this->assertTrue($plans[0]->is($freePlan));
        $this->assertTrue($plans[1]->is($basicPlan));
        $this->assertTrue($plans[2]->is($proPlan));
        $this->assertTrue($plans[3]->is($teamPlan));
    }

    /** @test */
    public function plans_can_be_compared()
    {
        $basicPlan = Plan::create([
            'description' => 'Basic plan',
            'name' => 'basic',
            'is_free' => false,
            'weight' => 1,
        ]);

        $proPlan = Plan::create([
            'description' => 'Pro plan',
            'name' => 'pro',
            'is_free' => false,
            'weight' => 3,
        ]);

        $freePlan = Plan::create([
            'description' => 'Free plan',
            'name' => 'free',
            'is_free' => true,
            'weight' => 0,
        ]);

        $freePricingPlan = PricingPlan::create([
            'name' => 'free_plan_0',
            'description' => 'Free plan',
            'plan_id' => $freePlan->id,
            'price' => 0
        ]);

        $proMonthlyPricingPlan = PricingPlan::create([
            'name' => 'pro_monthly_plan',
            'description' => 'Pro monthly',
            'price' => 3000,
            'plan_id' => $proPlan->id,
            'interval' => 'month'
        ]);

        $proYearlyPricingPlan = PricingPlan::create([
            'name' => 'pro_yearly_plan',
            'description' => 'Pro yearly',
            'price' => 45000,
            'plan_id' => $proPlan->id,
            'interval' => 'year'
        ]);

        $basicYearlyPricingPlan = PricingPlan::create([
            'name' => 'basic_yearly_plan',
            'description' => 'Basic yearly',
            'price' => 27000,
            'plan_id' => $basicPlan->id,
            'interval' => 'year'
        ]);

        $justMonthlyPlan = PricingPlan::create([
            'name' => 'simple_monthly',
            'description' => 'Simple monthly',
            'price' => 2700,
            'interval' => 'month'
        ]);

        $justYearlyPlan = PricingPlan::create([
            'name' => 'simple_yearly',
            'description' => 'Simple yearly',
            'price' => 45000,
            'interval' => 'year'
        ]);

        $this->assertTrue($proMonthlyPricingPlan->isBetterThan($freePricingPlan));
        $this->assertTrue($freePricingPlan->isWorthThan($proMonthlyPricingPlan));
        $this->assertFalse($proMonthlyPricingPlan->isBetterThan($proYearlyPricingPlan));
        $this->assertFalse($proMonthlyPricingPlan->isWorthThan($proYearlyPricingPlan));

        $this->assertTrue($proMonthlyPricingPlan->isBetterThan($basicYearlyPricingPlan));
        $this->assertTrue($basicYearlyPricingPlan->isWorthThan($proMonthlyPricingPlan));

        $this->assertTrue($justYearlyPlan->isBetterThan($justMonthlyPlan));
        $this->assertTrue($justMonthlyPlan->isWorthThan($justYearlyPlan));

        // because more expensive
        // but generally comparison of plans with and without weight should not happen
        $this->assertTrue($justYearlyPlan->isBetterThan($basicYearlyPricingPlan));
    }
}