<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use TMyers\StripeBilling\Models\PricingPlan;
use TMyers\StripeBilling\Models\Plan;

trait PlanFactory
{
    /**
     * @param array $overrides
     * @return Plan
     */
    protected function createFreePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'description' => 'Free plan',
            'name' => 'free',
            'is_free' => true,
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return Plan
     */
    protected function createBasicPlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'description' => 'Basic plan',
            'name' => 'basic',
            'is_free' => false,
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return Plan
     */
    protected function createTeamPlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'description' => 'Team plan',
            'name' => 'team',
            'teams_enabled' => true,
            'is_free' => false,
            'team_users_limit' => 10,
        ], $overrides));
    }

    /**
     * @param Plan $plan
     * @param array $attributes
     * @return PricingPlan
     */
    protected function createPricingPlan(Plan $plan, array $attributes): PricingPlan
    {
        return $plan->pricingPlans()->create($attributes);
    }

    /**
     * @param Plan $type
     * @param array $overrides
     * @return PricingPlan
     */
    protected function createMonthlyPricingPlan(array $overrides = []): PricingPlan
    {
        return PricingPlan::create(array_merge([
            'plan_id' => null,
            'description' => 'Monthly plan',
            'name' => 'monthly',
            'interval' => 'month',
            'stripe_plan_id' => 'monthly',
            'price' => 2000,
            'active' => true,
            'trial_days' => 1,
        ], $overrides));
    }

    /**
     * @param Plan|null $plan
     * @param array $overrides
     * @return PricingPlan
     */
    protected function createBasicMonthlyPricingPlan(Plan $plan = null, array $overrides = []): PricingPlan
    {
        $plan = $plan ?: $this->createBasicPlan();

        return PricingPlan::create(array_merge([
            'plan_id' => $plan->id,
            'description' => 'Basic monthly plan',
            'name' => 'basic-monthly',
            'interval' => 'month',
            'stripe_plan_id' => 'basic_monthly',
            'price' => 1500,
            'trial_days' => 11,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param Plan|null $plan
     * @param array $overrides
     * @return PricingPlan
     */
    protected function createBasicYearlyPricingPlan(Plan $plan = null, array $overrides = []): PricingPlan
    {
        $plan = $plan ?: $this->createBasicPlan();

        return PricingPlan::create(array_merge([
            'plan_id' => $plan->id,
            'description' => 'Basic yearly plan',
            'name' => 'basic-yearly-9000',
            'interval' => 'month',
            'stripe_plan_id' => 'basic_yearly_9000',
            'price' => 90000,
            'trial_days' => 33,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param Plan|null $plan
     * @param array $overrides
     * @return PricingPlan
     */
    protected function createTeamMonthlyPricingPlan(Plan $plan = null, array $overrides = []): PricingPlan
    {
        $plan = $plan ?: $this->createTeamPlan();

        return PricingPlan::create(array_merge([
            'plan_id' => $plan->id,
            'description' => 'Team monthly plan for 10 users',
            'name' => 'team-monthly-10',
            'interval' => 'month',
            'stripe_plan_id' => 'team_monthly_10',
            'price' => 4500,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return PricingPlan
     */
    public function createInactivePricingPlan(array $overrides = []): PricingPlan
    {
        return PricingPlan::create(array_merge([
            'description' => 'Team monthly plan for 24 users',
            'name' => 'team-monthly-24',
            'interval' => 'month',
            'stripe_plan_id' => 'team_monthly_24',
            'price' => 5500,
            'active' => false,
        ], $overrides));
    }
}