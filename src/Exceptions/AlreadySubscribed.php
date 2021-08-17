<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 18.10.2018
 * Time: 16:56
 */

namespace TMyers\StripeBilling\Exceptions;


class AlreadySubscribed extends StripeBillingException {
    public static function toPlan($plan): self {
        $planName = is_string($plan) ? $plan : $plan->name;

        return new static("Already subscibed to {$planName}");
    }
}
