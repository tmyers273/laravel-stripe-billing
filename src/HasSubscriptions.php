<?php

namespace TMyers\StripeBilling;


use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\SubscriptionNotFound;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\PlanType;
use TMyers\StripeBilling\Models\Subscription;

trait HasSubscriptions
{
    /**
     * @param Plan|PlanType|string $plan
     * @return bool
     */
    public function isSubscribedTo($plan): bool
    {
        $subscriptions = $this->activeSubscriptions;

        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            if ($subscription->isFor($plan)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $plan
     * @param null $token
     * @param array $options
     * @return mixed
     * @throws AlreadySubscribed
     */
    public function subscribeTo($plan, $token = null, array $options = []): Subscription
    {
        if (is_null($plan)) {
            throw new \InvalidArgumentException("Plan cannot be null.");
        }

        if (is_string($plan)) {
            $plan = Plan::fromCodeName($plan);
        }

        if ($this->isSubscribedTo($plan)) {
            throw AlreadySubscribed::toPlan($plan);
        }

        $builder = new StripeSubscriptionBuilder($this, $plan);

        return $builder->create($token, $options);
    }

    /**
     * @param $plan
     * @return Subscription
     * @throws SubscriptionNotFound
     */
    public function getSubscriptionFor($plan): Subscription
    {
        $found = $this->activeSubscriptions->first(function(Subscription $subscription) use ($plan) {
            return $subscription->isStrictlyFor($plan);
        });

        if (!$found) {
            throw SubscriptionNotFound::forPlan($plan);
        }

        return $found;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function subscriptions()
    {
        return $this->hasMany(config('stripe-billing.models.subscription'), 'owner_id');
    }

    public function activeSubscriptions()
    {
        return $this
            ->hasMany(config('stripe-billing.models.subscription'), 'owner_id')
            ->where(function($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}