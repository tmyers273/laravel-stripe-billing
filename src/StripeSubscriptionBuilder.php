<?php

namespace TMyers\StripeBilling;


use Carbon\Carbon;
use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\Models\Price;
use TMyers\StripeBilling\Models\Subscription;

class StripeSubscriptionBuilder
{
    protected $owner;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var bool
     */
    protected $skipTrial;

    /**
     * StripeSubscriptionBuilder constructor.
     *
     * @param $owner
     * @param Price $price
     */
    public function __construct($owner, $price)
    {
        $this->owner = $owner;
        $this->price = $price;
        $this->skipTrial = $this->price->trial_days === 0;
    }

    /**
     * @param null $token
     * @param array $options
     * @return Subscription
     */
    public function create($token = null, array $options = [])
    {
        $subscription = StripeSubscription::create(
            $this->owner->retrieveOrCreateStripeCustomer($token, $options),
            $this->getSubscriptionOptions()
        );

        if ($this->skipTrial) {
            $trialEndsAt = null;
        } else {
            $trialEndsAt = $this->getTrialExpiresAt();
        }

        return $this->owner->subscriptions()->create([
            'price_id' => $this->price->id,
            'stripe_subscription_id' => $subscription->id,
            'type' => $this->price->getType(),
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    /**
     * @return int|string
     */
    protected function getTrialEnd()
    {
        if ($this->skipTrial) {
            return 'now';
        }

        return $this->getTrialExpiresAt()->getTimestamp();
    }

    /**
     * @return Carbon
     */
    public function getTrialExpiresAt()
    {
        return Carbon::now()->addDays($this->price->trial_days);
    }

    /**
     * @return array
     */
    protected function getSubscriptionOptions(): array
    {
        return array_filter([
            'product' => $this->price->stripe_product_id,
            'trial_end' => $this->getTrialEnd(),
        ]);
    }
}
