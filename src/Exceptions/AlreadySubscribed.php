<?php

namespace TMyers\StripeBilling\Exceptions;

class AlreadySubscribed extends StripeBillingException
{
    public static function toPrice($price): self
    {
        $priceName = is_string($price) ? $price : $price->name;

        return new static("Already subscribed to {$priceName}");
    }
}
