<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use Carbon\Carbon;
use TMyers\StripeBilling\Models\StripePrice;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

trait SubscriptionFactory
{
    public function createActiveSubscription(User $user, StripePrice $price, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'price_id' => $price->id,
            'stripe_subscription_id' => 'fake-stripe-id',
        ], $overrides));
    }

    public function createOnTrialSubscription(User $user, StripePrice $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'price_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
            'trial_ends_at' => Carbon::now()->addDays(25),
        ], $overrides));
    }

    public function createGraceSubscription(User $user, StripePrice $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'price_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
            'ends_at' => now()->addDays(17),
        ], $overrides));
    }

    /**
     * @param User $user
     * @param StripePrice $plan
     * @param array $overrides
     * @return Subscription
     */
    public function createExpiredSubscription(User $user, StripePrice $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'price_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
            'ends_at' => now()->subDays(17),
        ], $overrides));
    }
}
