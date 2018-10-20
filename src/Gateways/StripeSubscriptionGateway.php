<?php

namespace TMyers\StripeBilling\Gateways;


use Carbon\Carbon;
use Stripe\Customer;
use Stripe\Subscription;

class StripeSubscriptionGateway extends StripeGateway
{
    /**
     * @param Customer $customer
     * @param array $options
     * @return Subscription
     */
    public function create($customer, array $options)
    {
        return $customer->subscriptions->create($options);
    }

    /**
     * @param string $subscriptionId
     * @return \Stripe\Subscription
     */
    public function retrieve(string $subscriptionId)
    {
        return Subscription::retrieve($subscriptionId);
    }

    /**
     * @param Subscription $subscription
     * @return Carbon
     */
    public function parseCurrentPeriodEnd(Subscription $subscription): Carbon
    {
        return Carbon::createFromTimestamp(
            $subscription->current_period_end
        );
    }
}