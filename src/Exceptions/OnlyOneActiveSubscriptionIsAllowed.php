<?php

namespace TMyers\StripeBilling\Exceptions;


class OnlyOneActiveSubscriptionIsAllowed extends StripeBillingException {
    /**
     * @return OnlyOneActiveSubscriptionIsAllowed
     */
    public static function new() {
        return new static("Only one active subscription is allowed. If this is not intended behaviour, check the config file.");
    }
}
