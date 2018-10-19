<?php

namespace TMyers\StripeBilling\Gateways;


use TMyers\StripeBilling\Exceptions\StripeGatewayException;

class StripeGateway
{
    /**
     * @var string
     */
    protected static $apiKey;

    /**
     * @param string $apiKey
     */
    public static function setApiKey(string $apiKey)
    {
        static::$apiKey = $apiKey;
    }

    /**
     * @return string
     * @throws StripeGatewayException
     */
    public function getApiKey(): string
    {
        if (static::$apiKey) {
            return static::$apiKey;
        }

        if ($key = getenv('STRIPE_SECRET')) {
            return $key;
        }

        if ($key = config('services.stripe.secret')) {
            return $key;
        }

        throw new StripeGatewayException("Api key not set.");
    }
}