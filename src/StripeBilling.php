<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 27.10.2018
 * Time: 14:11
 */

namespace TMyers\StripeBilling;


class StripeBilling
{
    /**
     * @var string
     */
    protected static $currency = 'usd';

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
}