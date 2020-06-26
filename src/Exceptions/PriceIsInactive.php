<?php

namespace TMyers\StripeBilling\Exceptions;


class PriceIsInactive extends StripeBillingException
{
    public static function price($plan): self
    {
        return new static(
            "Price {$plan->name} with stripe id {$plan->stripe_price_id} is not active anymore"
        );
    }
}
