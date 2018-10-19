<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 19.10.2018
 * Time: 18:55
 */

namespace TMyers\StripeBilling;


use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;

trait Billable
{
    use HasSubscriptions;

    /**
     * @param null $token
     * @param array $options
     * @return Customer
     */
    public function retrieveOrCreateStripeCustomer($token = null, array $options = [])
    {
        if (! $this->stripe_id) {
            $customer = StripeCustomer::create($token, $this->email, $options);

            $this->update([
                'stripe_id' => $customer->id,
            ]);

            return $customer;
        }

        return StripeCustomer::retrieve($this->stripe_id);
    }
}