<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 18.10.2018
 * Time: 23:31
 */

namespace TMyers\StripeBilling\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * Class StripeSubscription
 * @package TMyers\StripeBilling\Facades
 * @method static retrieve(string $stripeSubscriptionId)
 */
class StripeSubscription extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'stripe-subscription-gateway';
    }
}