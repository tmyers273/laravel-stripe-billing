<?php

namespace TMyers\StripeBilling\Gateways;


use Stripe\Card;
use Stripe\Customer;
use Stripe\Token;
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
        } catch (\Stripe\Error\Card $e) {
            throw StripeGatewayException::cardDeclined($e);
        } catch (\Throwable $t) {
            throw new StripeGatewayException($t->getMessage(), $t->getCode(), $t);
        }


        return $customer;
    }

    /**
     * @param string $stripeId
     * @return \Stripe\StripeObject
     * @throws StripeGatewayException
     */
    public function retrieve(string $stripeId)
    {
        return Customer::retrieve($stripeId, $this->getApiKey());
    }

    /**
     * @param Customer $customer
     * @param $token
     * @return Card
     */
    public function createSource(Customer $customer, string $token): Card
    {
        return $customer->sources->create(['source' => $token]);
    }

    /**
     * @param Customer $stripeCustomer
     * @param string $token
     * @return bool
     * @throws StripeGatewayException
     */
    public function isDefaultSource(Customer $stripeCustomer, string $token): bool
    {
        $stripeToken = Token::retrieve($token, ['api_key' => $this->getApiKey()]);

        return $stripeToken[$stripeToken->type]->id === $stripeCustomer->default_source;
    }

    /**
     * @param Customer $customer
     * @param string $sourceId
     */
    public function deleteSource(Customer $customer, string $sourceId)
    {
        /** @var \Stripe\Card $stripeCard */
        foreach ($customer->sources->data as $stripeCard) {
            if ($stripeCard->id === $sourceId) {
                $stripeCard->delete();
            }
        }
    }

    /**
     * @param Customer $customer
     * @return array
     */
    public function parseDefaultCard(Customer $customer): array
    {
        return [
            'stripe_card_id' => $customer->sources->data[0]->id,
            'brand' => $customer->sources->data[0]->brand,
            'last_4' => $customer->sources->data[0]->last4,
        ];
    }
}