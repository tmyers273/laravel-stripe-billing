<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 20.10.2018
 * Time: 16:30
 */

namespace TMyers\StripeBilling\Exceptions;


use TMyers\StripeBilling\Models\Plan;

class PlanIsInactive extends \Exception
{
    public static function plan(Plan $plan): self
    {
        return new static(
            "Plan {$plan->name} with code name {$plan->code_name} and stripe id {$plan->stripe_plan_id} is not active anymore"
        );
    }
}