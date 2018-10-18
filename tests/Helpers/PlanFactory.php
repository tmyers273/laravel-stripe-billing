<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\PlanType;

trait PlanFactory
{
    /**
     * @param array $overrides
     * @return PlanType
     */
    protected function createFreePlanType(array $overrides = []): PlanType
    {
        return PlanType::create(array_merge([
            'name' => 'Free plan',
            'code_name' => 'free',
            'is_free' => true,
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return PlanType
     */
    protected function createBasicPlanType(array $overrides = []): PlanType
    {
        return PlanType::create(array_merge([
            'name' => 'Basic plan',
            'code_name' => 'basic',
            'is_free' => false,
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return PlanType
     */
    protected function createTeamPlanType(array $overrides = []): PlanType
    {
        return PlanType::create(array_merge([
            'name' => 'Team plan',
            'code_name' => 'team',
            'teams_enabled' => true,
            'is_free' => false,
            'team_users_limit' => 10,
        ], $overrides));
    }

    /**
     * @param PlanType $type
     * @param array $overrides
     * @return Plan
     */
    protected function createMonthlyPlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'plan_type_id' => null,
            'name' => 'Monthly plan',
            'code_name' => 'monthly',
            'interval' => 'month',
            'stripe_plan_id' => 'monthly',
            'price' => 2000,
        ], $overrides));
    }

    /**
     * @param PlanType $type
     * @param array $overrides
     * @return Plan
     */
    protected function createBasicMonthlyPlan(PlanType $type = null, array $overrides = []): Plan
    {
        $type = $type ?: $this->createBasicPlanType();

        return Plan::create(array_merge([
            'plan_type_id' => $type->id,
            'name' => 'Basic monthly plan',
            'code_name' => 'basic-monthly',
            'interval' => 'month',
            'stripe_plan_id' => 'basic_monthly',
            'price' => 1500,
        ], $overrides));
    }

    /**
     * @param PlanType $type
     * @param array $overrides
     * @return Plan
     */
    protected function createTeamMonthlyPlan(PlanType $type = null, array $overrides = []): Plan
    {
        $type = $type ?: $this->createTeamPlanType();

        return Plan::create(array_merge([
            'plan_type_id' => $type->id,
            'name' => 'Team monthly plan for 10 users',
            'code_name' => 'team-monthly-10',
            'interval' => 'month',
            'stripe_plan_id' => 'team_monthly_10',
            'price' => 4500,
        ], $overrides));
    }
}