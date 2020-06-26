<?php

namespace TMyers\StripeBilling;


use Illuminate\Support\Collection;
use Stripe\Coupon;
use Stripe\Customer;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\OnlyOneActiveSubscriptionIsAllowed;
use TMyers\StripeBilling\Exceptions\SubscriptionNotFound;
use TMyers\StripeBilling\Models\StripePrice;
use TMyers\StripeBilling\Models\StripeProduct;
use TMyers\StripeBilling\Models\Subscription;

/**
 * Trait HasSubscriptions
 * @package TMyers\StripeBilling
 *
 * @property Collection $activeSubscriptions
 * @property Collection $subscriptions
 */
trait HasSubscriptions
{
    /**
     * @param StripePrice|StripeProduct|string $plan
     * @return bool
     */
    public function isSubscribedTo($plan): bool
    {
        $subscriptions = $this->activeSubscriptions;

        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            if ($subscription->isFor($plan) && $subscription->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $price
     * @return bool
     */
    public function isSubscribedStrictlyTo($price): bool
    {
        $subscriptions = $this->activeSubscriptions;

        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            if ($subscription->isStrictlyFor($price) && $subscription->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $price
     * @param int $trialDays
     * @param null $token
     * @param array $options
     * @return mixed
     * @throws AlreadySubscribed
     * @throws OnlyOneActiveSubscriptionIsAllowed
     */
    public function subscribeTo($price, int $trialDays, $token = null, array $options = []): Subscription
    {
        if (is_null($price)) {
            throw new \InvalidArgumentException("Price cannot be null.");
        }

        if ($this->canHaveOnlyOneSubscription() && $this->hasActiveSubscriptions()) {
            throw OnlyOneActiveSubscriptionIsAllowed::new();
        }

        if (is_string($price)) {
            $priceModel = StripeBilling::getPricesModel();
            $price = $priceModel::findByName($price);
        }

        if ($this->isSubscribedTo($price)) {
            throw AlreadySubscribed::toPrice($price);
        }

        $builder = new StripeSubscriptionBuilder($this, $price);
        return $builder->create($trialDays, $token, $options);
    }

    /**
     * @param $plan
     * @return Subscription
     * @throws SubscriptionNotFound
     */
    public function getSubscriptionFor($plan)
    {
        $found = $this->activeSubscriptions->first(function($subscription) use ($plan) {
            return $subscription->isStrictlyFor($plan);
        });

        if (!$found) {
            throw SubscriptionNotFound::forPlan($plan);
        }

        return $found;
    }

    /**
     * @param string|Coupon $coupon
     * @return Customer
     */
    public function applyCoupon($coupon): Customer
    {
        $customer = $this->retrieveStripeCustomer();

        $customer->coupon = $coupon;

        $customer->save();

        return $customer;
    }

    /**
     * @return bool
     */
    public function hasActiveSubscriptions(): bool
    {
        return $this->activeSubscriptions->count() > 0;
    }

    /**
     * @return mixed
     * @throws SubscriptionNotFound
     */
    public function getFirstActiveSubscription()
    {
        $found = $this->activeSubscriptions->first();

        if (!$found) {
            throw new SubscriptionNotFound("User [{$this->id}] does not have any active subscriptions.");
        }

        return $found;
    }

    /**
     * @return bool
     */
    public function canHaveOnlyOneSubscription(): bool
    {
        return !! config('stripe-billing.unique_active_subscription');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function subscriptions()
    {
        return $this->hasMany(StripeBilling::getSubscriptionModel(), 'owner_id');
    }

    public function activeSubscriptions()
    {
        return $this
            ->hasMany(StripeBilling::getSubscriptionModel(), 'owner_id')
            ->where(function($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
