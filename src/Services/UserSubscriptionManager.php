<?php

namespace TMyers\StripeBilling\Services;

use TMyers\StripeBilling\BillableContract;
use TMyers\StripeBilling\Models\Card;
use TMyers\StripeBilling\Models\StripePrice;
use TMyers\StripeBilling\Models\Subscription;

class UserSubscriptionManager {

    /**
     * @param BillableContract $user
     */
    public function resetUserCards(BillableContract $user) {
        $user->cards()->delete();
        $user->forceFill([
            'stripe_id' => null,
            'default_card_id' => null,
        ])->save();
    }

    /**
     * @param BillableContract $user
     * @param StripePrice $price
     * @param string $token
     * @return Subscription
     */
    public function subscribe(BillableContract $user, StripePrice $price, $token = null): Subscription {
        return $user->subscribeTo($price, $token);
    }

    /**
     * @param BillableContract $user
     * @param string $token
     * @return Card
     */
    public function newDefaultCard(BillableContract $user, string $token): Card {
        return $user->addCardFromToken($token);
    }

    /**
     * @param Subscription $subscription
     * @param StripePrice $price
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \TMyers\StripeBilling\Exceptions\AlreadySubscribed
     * @throws \TMyers\StripeBilling\Exceptions\PriceIsInactive
     */
    public function updateSubscription(Subscription $subscription, StripePrice $price) {
        $subscription->changeTo($price);
    }

    /**
     * @param Subscription $subscription
     * @throws \TMyers\StripeBilling\Exceptions\StripeBillingException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function resumeSubscription(Subscription $subscription) {
        $subscription->resume();
    }

    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = true) {
        $atPeriodEnd ? $subscription->cancelAtPeriodEnd() : $subscription->cancelNow();
    }
}
