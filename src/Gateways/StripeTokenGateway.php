<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 24.10.2018
 * Time: 0:39
 */

namespace TMyers\StripeBilling\Gateways;


use Stripe\Card;
use Stripe\Customer;
use Stripe\Token;

class StripeTokenGateway extends StripeGateway
{
    /**
     * @param $token
     * @return \Stripe\Token
     * @throws \TMyers\StripeBilling\Exceptions\StripeGatewayException
     */
    public function retrieve($token): Token
    {
        return Token::retrieve($token, ['api_key' => $this->getApiKey()]);
    }

    /**
     * @param Token $stripeToken
     * @param $token
     * @return Card
     */
    public function createSource(Token $stripeToken, $token): Card
    {
        return $stripeToken->sources->create(['source' => $token]);
    }

    public function isDefaultSource(Token $stripeToken, Customer $stripeCustomer): bool
    {
        return $stripeToken[$stripeToken->type]->id === $stripeCustomer->default_source;
    }
}