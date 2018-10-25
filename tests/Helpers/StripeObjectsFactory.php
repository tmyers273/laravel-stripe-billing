<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 19.10.2018
 * Time: 18:29
 */

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
}