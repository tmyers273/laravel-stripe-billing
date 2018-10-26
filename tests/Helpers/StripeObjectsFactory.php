<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use Stripe\Card;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Token;

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

    protected function createTestToken(): string
    {
        return \Stripe\Token::create([
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 5,
                'exp_year' => 2020,
                'cvc' => '123',
            ],
        ], ['api_key' => getenv('STRIPE_SECRET')])->id;
    }
}