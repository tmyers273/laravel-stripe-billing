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
    public function create($customer, array $options): Subscription {
        return $customer->subscriptions->create($options);
    }

    /**
     * @param string $stripeSubscriptionId
     * @return \Stripe\Subscription
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function retrieve(string $stripeSubscriptionId): Subscription {
        return $this->client->subscriptions->retrieve($stripeSubscriptionId);
    }

    /**
     * @param Subscription $subscription
     * @return Carbon
     */
    public function parseCurrentPeriodEnd(Subscription $subscription): Carbon {
        return Carbon::createFromTimestamp(
            $subscription->current_period_end
        );
    }
}
