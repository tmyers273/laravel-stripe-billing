<?php

namespace TMyers\StripeBilling;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Models\Card;
use TMyers\StripeBilling\Models\Subscription;

interface BillableContract {

    public function retrieveStripeCustomer(): Customer;
    public function retrieveOrCreateStripeCustomer($token = null, array $options = []);
    public function createCustomerWithDefaultCardFromToken($token, array $options = []);

    public function isSubscribedTo($plan): bool;
    public function isSubscribedStrictlyTo($price): bool;
    public function subscribeTo($price, int $trialDays, $token = null, array $options = []): Subscription;
    public function getSubscriptionFor($plan);

    public function applyCoupon($coupon): Customer;
    public function hasActiveSubscriptions(): bool;
    public function getFirstActiveSubscription();
    public function canHaveOnlyOneSubscription(): bool;

    public function subscriptions();
    public function activeSubscriptions();

    public function addNewDefaultCard(array $data);
    public function addCardFromToken(string $token);
    public function setCardAsDefault($card);
    public function removeCard($card);
    public function hasDefaultCard($card = null): bool;

    public function charge(int $amount, array $params = []);
    public function chargeCard(int $amount, $card, array $params = []);
    public function chargeByToken(int $amount, string $token, array $params = []);

    public function cards();
    public function defaultCard();
}
