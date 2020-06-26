<?php

namespace TMyers\StripeBilling\Gateways;

use Stripe\StripeClient;
use TMyers\StripeBilling\StripeBilling;

class StripeGateway
{
    public function __construct() {
        $this->client = new StripeClient(StripeBilling::getApiKey());
    }

    /**
     * @return string
     * @throws \TMyers\StripeBilling\Exceptions\StripeBillingException
     */
    public function getApiKey(): string
    {
        return StripeBilling::getApiKey();
    }
}
