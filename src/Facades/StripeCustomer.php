<?php

namespace TMyers\StripeBilling\Facades;


use Illuminate\Support\Facades\Facade;
use Stripe\Customer;

/**
 * Class StripeCustomer
 * @package TMyers\StripeBilling\Facades\
 * @method  static retrieve($stripeId): \Stripe\Customer
 * @method static parseDefaultCard($customer): array
 * @method static isDefaultSource(Customer $stripeCustomer, string $token): bool
 * @method static createSource(Customer $customer, string $token): \Stripe\Card
 */
class StripeCustomer extends Facade
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
        return 'stripe-customer-gateway';
    }
}