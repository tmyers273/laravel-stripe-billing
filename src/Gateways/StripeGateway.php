<?php

namespace TMyers\StripeBilling\Gateways;


use TMyers\StripeBilling\Exceptions\StripeGatewayException;
use TMyers\StripeBilling\StripeBilling;

class StripeGateway
{
    /**
     * @return string
     * @throws \TMyers\StripeBilling\Exceptions\StripeBillingException
     */
    public function getApiKey(): string
    {
        return StripeBilling::getApiKey();
    }
}