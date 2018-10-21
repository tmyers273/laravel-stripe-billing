<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 20.10.2018
 * Time: 16:30
 */

namespace TMyers\StripeBilling\Exceptions;


use TMyers\StripeBilling\Models\PricingPlan;

class PlanIsInactive extends StripeBillingException
{
    public static function plan(PricingPlan $plan): self
    {
        return new static(
            "PricingPlan {$plan->name} with stripe id {$plan->stripe_plan_id} is not active anymore"
        );
    }
}