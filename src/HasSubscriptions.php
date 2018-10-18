<?php

namespace TMyers\StripeBilling;


use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Models\Plan;

trait HasSubscriptions
{
    public function isSubscribedTo($plan): bool
    {
        $subscriptions = $this->activeSubscriptions;

        foreach ($subscriptions as $subscription) {
            if ($subscription->isForPlan($plan)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $plan
     * @param null $token
     * @return mixed
     * @throws AlreadySubscribed
     */
    public function subscribeTo($plan, $token = null)
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

        $type = $plan->planType ? $plan->planType->code_name : 'deafult';

        // @TODO
        // CustomerManager::createCustomer($token);
        // SubscriptionManager::createSubscription();
        return $this->subscriptions()->create([
            'plan_id' => $plan->id,
            'type' => $type,
        ]);
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