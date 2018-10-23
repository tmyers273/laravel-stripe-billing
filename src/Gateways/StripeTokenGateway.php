<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 24.10.2018
 * Time: 0:39
 */

namespace TMyers\StripeBilling\Gateways;


use Stripe\Token;

class StripeTokenGateway extends StripeGateway
{
    /**
     * @param $token
     * @return \Stripe\StripeObject
     * @throws \TMyers\StripeBilling\Exceptions\StripeGatewayException
     */
    public function retrieve($token)
    {
        return Token::retrieve($token, ['api_key' => $this->getApiKey()]);
    }
}