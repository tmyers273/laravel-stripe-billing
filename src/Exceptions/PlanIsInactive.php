<?php

namespace TMyers\StripeBilling\Exceptions;


class PlanIsInactive extends StripeBillingException
{
    public static function plan($plan): self
    {
        return new static(
            "PricingPlan {$plan->name} with stripe id {$plan->stripe_plan_id} is not active anymore"
        );
    }
}