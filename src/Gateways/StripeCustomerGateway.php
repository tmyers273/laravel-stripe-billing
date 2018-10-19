<?php

namespace TMyers\StripeBilling\Gateways;


use Stripe\Customer;
use Stripe\Error\Card;
use TMyers\StripeBilling\Exceptions\StripeGatewayException;

class StripeCustomerGateway extends StripeGateway
{
    /**
     * @param $token
     * @param string $email
     * @param array $options
     * @return \Stripe\ApiResource
     * @throws \TMyers\StripeBilling\Exceptions\StripeGatewayException
     */
    public function create(string $token, string $email, array $options = [])
    {
        try {
            $options = array_merge($options, [
                'email' => $email,
                'source' => $token,
            ]);

            $customer = Customer::create($options, $this->getApiKey());
        } catch (Card $e) {
            throw StripeGatewayException::cardDeclined($e);
        } catch (\Throwable $t) {
            throw new StripeGatewayException($t->getMessage(), $t->getCode(), $t);
        }


        return $customer;
    }

    /**
     * @param string $stripeId
     * @return \Stripe\StripeObject
     */
    public function retrieve(string $stripeId)
    {
        return Customer::retrieve($stripeId);
    }

    public function parseDefaultCard(Customer $customer): array
    {
        return [
            'stripe_card_id' => $customer->sources->data[0]->id,
            'brand' => $customer->sources->data[0]->brand,
            'last_4' => $customer->sources->data[0]->last4,
        ];
    }
}