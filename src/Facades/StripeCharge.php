<?php

namespace TMyers\StripeBilling\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * Class StripeCharge
 * @package TMyers\StripeBilling\Facades
 *
 * @method static charge(array $params = []): Charge
 */
class StripeCharge extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'stripe-charge-gateway';
    }
}