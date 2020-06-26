<?php

namespace TMyers\StripeBilling;


use TMyers\StripeBilling\Exceptions\StripeBillingException;

class StripeBilling
{
    /**
     * @var string
     */
    protected static $currency = 'usd';

    /**
     * @var string|null
     */
    protected static $apiKey;

    /**
     * @return string
     */
    public static function getCurrency(): string
    {
        return static::$currency;
    }

    /**
     * @param string $currency
     */
    public static function setCurrency(string $currency)
    {
        static::$currency = $currency;
    }

    /**
     * @return string
     */
    public static function getOwnerModel(): string
    {
        return config('stripe-billing.models.owner');
    }

    /**
     * @return string
     */
    public static function getCardModel(): string
    {
        return config('stripe-billing.models.card');
    }

    /**
     * @return string
     */
    public static function getSubscriptionModel(): string
    {
        return config('stripe-billing.models.subscription');
    }

    /**
     * @return string
     */
    public static function getProductModel(): string
    {
        return config('stripe-billing.models.product');
    }

    /**
     * @return string
     */
    public static function getPricesModel(): string
    {
        return config('stripe-billing.models.prices');
    }

    /**
     * @param string $apiKey
     */
    public static function setApiKey(string $apiKey)
    {
        static::$apiKey = $apiKey;
    }

    /**
     * @return array|false|\Illuminate\Config\Repository|mixed|null|string
     * @throws StripeBillingException
     */
    public static function getApiKey()
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

        throw new StripeBillingException("Api key not set.");
    }

    /**
     * @return mixed|null
     * @throws StripeBillingException
     */
    public static function createTestToken()
    {
        return \Stripe\Token::create([
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 5,
                'exp_year' => 2025,
                'cvc' => '123',
            ],
        ], ['api_key' => static::getApiKey()])->id;
    }
}
