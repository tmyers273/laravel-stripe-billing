<?php

namespace TMyers\StripeBilling;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Models\Card;

trait Billable
{
    use HasSubscriptions, Chargeable;

    /**
     * @return \Stripe\Customer
     */
    public function retrieveStripeCustomer(): \Stripe\Customer
    {
        return StripeCustomer::retrieve($this->stripe_id);
    }

    /**
     * @param null $token
     * @param array $options
     * @return \Stripe\Customer
     */
    public function retrieveOrCreateStripeCustomer($token = null, array $options = [])
    {
        if (! $this->stripe_id) {
            list($customer, $card) = $this->createCustomerWithDefaultCardFromToken($token, $options);

            return $customer;
        }

        return $this->retrieveStripeCustomer();
    }

    /**
     * @param $token
     * @param array $options
     * @return array
     */
    public function createCustomerWithDefaultCardFromToken($token, array $options = []): array
    {
        $customer = StripeCustomer::create($token, $this->email, $options);

        $this->forceFill([
            'stripe_id' => $customer->id,
        ])->save();

        $card = $this->addNewDefaultCard(
            StripeCustomer::parseDefaultCard($customer)
        );

        return [$customer, $card];
    }
}