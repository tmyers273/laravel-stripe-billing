<?php

namespace TMyers\StripeBilling\Gateways;

use Stripe\Card;
use Stripe\Customer;
use Stripe\Exception\CardException;
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
    public function create(string $token, string $email, array $options = []) {
        try {
            $options = array_merge($options, [
                'email' => $email,
                'source' => $token,
            ]);

            $customer = $this->client->customers->create($options);
        } catch (CardException $e) {
            throw StripeGatewayException::cardDeclined($e);
        } catch (\Throwable $t) {
            throw new StripeGatewayException($t->getMessage(), $t->getCode(), $t);
        }

        return $customer;
    }

    /**
     * @param string $stripeId
     * @return Customer
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function retrieve(string $stripeId): Customer {
        return $this->client->customers->retrieve($stripeId);
    }

    /**
     * @param Customer $customer
     * @param $token
     * @return Card
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createSource(Customer $customer, string $token): Card {
        return $this->client->customers->createSource($customer->id, [
            'source' => $token,
        ]);
    }

    /**
     * @param Customer $stripeCustomer
     * @param string $token
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \TMyers\StripeBilling\Exceptions\StripeBillingException
     */
    public function isDefaultSource(Customer $stripeCustomer, string $token): bool
    {
        $stripeToken = Token::retrieve($token, ['api_key' => $this->getApiKey()]);

        return $stripeToken[$stripeToken->type]->id === $stripeCustomer->default_source;
    }

    /**
     * @param Customer $customer
     * @param string $sourceId
     * @throws \Stripe\Exception\ApiErrorException
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
            'exp_month' => $customer->sources->data[0]->exp_month,
            'exp_year' => $customer->sources->data[0]->exp_year,
        ];
    }
}
