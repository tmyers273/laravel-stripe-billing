<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 19.10.2018
 * Time: 18:55
 */

namespace TMyers\StripeBilling;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Models\Card;

trait Billable
{
    use HasSubscriptions, Chargeable;

    /**
     * @param null $token
     * @param array $options
     * @return Customer
     */
    public function retrieveOrCreateStripeCustomer($token = null, array $options = [])
    {
        if (! $this->stripe_id) {
            list($customer, $card) = $this->createCustomerWithDefaultCardFromToken($token, $options);

            return $customer;
        }

        return StripeCustomer::retrieve($this->stripe_id);
    }

    /**
     * @param $token
     * @param array $options
     * @return array
     */
    public function createCustomerWithDefaultCardFromToken($token, array $options = []): array
    {
        $customer = StripeCustomer::create($token, $this->email, $options);

        $this->update([
            'stripe_id' => $customer->id,
        ]);

        $card = $this->addNewDefaultCard(
            StripeCustomer::parseDefaultCard($customer)
        );

        return [$customer, $card];
    }
}