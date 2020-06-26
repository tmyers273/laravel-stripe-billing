<?php

namespace TMyers\StripeBilling\Services;

use TMyers\StripeBilling\Billable;
use TMyers\StripeBilling\Models\Card;
use TMyers\StripeBilling\Models\StripePrice;
use TMyers\StripeBilling\Models\Subscription;

class UserSubscriptionManager {

    /**
     * @param Billable $user
     */
    public function resetUserCards(Billable $user) {
        $user->cards()->delete();
        $user->forceFill([
            'stripe_id' => null,
            'default_card_id' => null,
        ])->save();
    }

    /**
     * @param Billable $user
     * @param StripePrice $price
     * @param string $token
     * @return Subscription
     * @throws \TMyers\StripeBilling\Exceptions\AlreadySubscribed
     * @throws \TMyers\StripeBilling\Exceptions\OnlyOneActiveSubscriptionIsAllowed
     */
    public function subscribe(Billable $user, StripePrice $price, $token = null): Subscription {
        return $user->subscribeTo($price, $token);
    }

    /**
     * @param Billable $user
     * @param string $token
     * @return Card
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function newDefaultCard(Billable $user, string $token): Card {
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
