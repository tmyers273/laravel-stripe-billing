<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 20.10.2018
 * Time: 14:07
 */

namespace TMyers\StripeBilling\Tests\Helpers;


use Carbon\Carbon;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

trait SubscriptionFactory
{
    public function createActiveSubscription(User $user, Plan $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
        ], $overrides));
    }

    public function createOnTrialSubscription(User $user, Plan $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
            'trial_ends_at' => Carbon::now()->addDays(25),
        ], $overrides));
    }

    public function createGraceSubscription(User $user, Plan $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
            'ends_at' => now()->addDays(17),
        ], $overrides));
    }

    /**
     * @param User $user
     * @param Plan $plan
     * @param array $overrides
     * @return Subscription
     */
    public function createExpiredSubscription(User $user, Plan $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
            'ends_at' => now()->subDays(17),
        ], $overrides));
    }
}