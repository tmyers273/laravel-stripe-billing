<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use Stripe\Card;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Token;
use TMyers\StripeBilling\StripeBilling;

trait StripeObjectsFactory
{
    /**
     * @param $id
     * @param array $opts
     * @return Customer
     */
    public function createCustomerObject($id, array $opts = []): Customer
    {
        return new Customer($id, $opts);
    }

    /**
     * @param $id
     * @param array $opts
     * @return Token
     */
    public function createTokenObject($id, array $opts = []): Token
    {
        return new Token($id, $opts);
    }

    /**
     * @param $id
     * @param array $opts
     * @return Token
     */
    public function createCardObject($id, array $opts = []): Card
    {
        return new Card($id, $opts);
    }

    /**
     * @param $id
     * @param array $opts
     * @return Subscription
     */
    public function createSubscriptionObject($id, array $opts = []): Subscription
    {
        return new Subscription($id, $opts);
    }

    /**
     * @return string
     * @throws \TMyers\StripeBilling\Exceptions\StripeBillingException
     */
    protected function createTestToken(): string
    {
        return StripeBilling::createTestToken();
    }
}