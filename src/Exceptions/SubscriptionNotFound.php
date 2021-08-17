<?php

namespace TMyers\StripeBilling\Exceptions;


class SubscriptionNotFound extends StripeBillingException {
    public static function forPlan($plan): self {
        $name = is_string($plan) ? $plan : $plan->name;

        return new static("Active subscription for plan {$name} not found");
    }
}
