<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 21.10.2018
 * Time: 17:41
 */

namespace TMyers\StripeBilling\Tests;


use Illuminate\Support\Str;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Price;
use TMyers\StripeBilling\Models\Product;

class PlanModelTest extends TestCase
{
    /** @test */
    public function it_can_have_multiple_pricing_plans()
    {
        // Given
        $basic = $this->createBasicPlan();

        $yearlyPlan = $this->createPrice($basic, [
            'name' => 'basic_yearly',
            'price' => 9900,
            'interval' => '1 year',
            'active' => true,
        ]);

        $montlyPlan = $this->createPrice($basic, [
            'name' => 'basic_monthly',
            'price' => 2500,
            'interval' => '1 month',
            'active' => true,
        ]);

        $pricing = $basic->prices;

        $this->assertCount(2, $pricing);

        $this->assertTrue($pricing[0]->is($montlyPlan));
        $this->assertEquals('basic', $pricing[0]->getType());
        $this->assertInstanceOf(Product::class, $pricing[0]->product);

        $this->assertTrue($pricing[1]->is($yearlyPlan));
        $this->assertEquals('basic', $pricing[1]->getType());
        $this->assertInstanceOf(Product::class, $pricing[1]->product);
    }

    /** @test */
    public function it_can_view_all_underlying_subscriptions()
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        // Given
        $basic = $this->createBasicPlan();

        $yearlyPlan = $this->createPrice($basic, [
            'name' => 'basic_yearly',
            'price' => 9900,
            'interval' => '1 year',
            'active' => true,
        ]);

        $montlyPlan = $this->createPrice($basic, [
            'name' => 'basic_monthly',
            'price' => 2500,
            'interval' => '1 month',
            'active' => true,
        ]);

        $subscriptionA = $this->createActiveSubscription($userA, $montlyPlan);
        $subscriptionB = $this->createActiveSubscription($userB, $montlyPlan);
        $subscriptionC = $this->createActiveSubscription($userA, $yearlyPlan);

        $basicSubscriptions = $basic->subscriptions;
        $prices = $basic->prices;

        $this->assertCount(3, $basicSubscriptions);
        $this->assertCount(2, $prices);

        $this->assertTrue($basicSubscriptions[0]->is($subscriptionA));
        $this->assertTrue($basicSubscriptions[1]->is($subscriptionB));
        $this->assertTrue($basicSubscriptions[2]->is($subscriptionC));
    }

    /** @test */
    public function products_can_be_compared()
    {
        $basicPlan = Product::create([
            'name' => 'basic',
            'stripe_product_id' => Str::random(),
        ]);

        $proPlan = Product::create([
            'name' => 'pro',
            'stripe_product_id' => Str::random(),
        ]);

        $freePlan = Product::create([
            'name' => 'free',
            'stripe_product_id' => Str::random(),
        ]);

        $freePrice = Price::create([
            'name' => 'free_plan_0',
            'product_id' => $freePlan->id,
            'price' => 0
        ]);

        $proMonthlyPrice = Price::create([
            'name' => 'pro_monthly_plan',
            'price' => 3000,
            'product_id' => $proPlan->id,
            'interval' => 'month'
        ]);

        $proYearlyPrice = Price::create([
            'name' => 'pro_yearly_plan',
            'price' => 45000,
            'product_id' => $proPlan->id,
            'interval' => 'year'
        ]);

        $basicYearlyPrice = Price::create([
            'name' => 'basic_yearly_plan',
            'price' => 27000,
            'product_id' => $basicPlan->id,
            'interval' => 'year'
        ]);

        $justMonthlyPlan = Price::create([
            'name' => 'simple_monthly',
            'price' => 2700,
            'interval' => 'month'
        ]);

        $justYearlyPlan = Price::create([
            'name' => 'simple_yearly',
            'price' => 45000,
            'interval' => 'year'
        ]);

        $this->assertTrue($proMonthlyPrice->isGreaterThan($freePrice));
        $this->assertTrue($freePrice->isLessThan($proMonthlyPrice));
        $this->assertFalse($proMonthlyPrice->isGreaterThan($proYearlyPrice));
        $this->assertTrue($proMonthlyPrice->isLessThan($proYearlyPrice));

        $this->assertFalse($proMonthlyPrice->isGreaterThan($basicYearlyPrice));
        $this->assertFalse($basicYearlyPrice->isLessThan($proMonthlyPrice));

        $this->assertTrue($justYearlyPlan->isGreaterThan($justMonthlyPlan));
        $this->assertTrue($justMonthlyPlan->isLessThan($justYearlyPlan));

        // because more expensive
        // but generally comparison of plans with and without weight should not happen
        $this->assertTrue($justYearlyPlan->isGreaterThan($basicYearlyPrice));
    }
}
