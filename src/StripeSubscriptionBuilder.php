<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 19.10.2018
 * Time: 16:40
 */

namespace TMyers\StripeBilling;


use Carbon\Carbon;
use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\Models\PricingPlan;
use TMyers\StripeBilling\Models\Subscription;

class StripeSubscriptionBuilder
{
    protected $owner;

    /**
     * @var PricingPlan
     */
    protected $pricingPlan;

    /**
     * @var bool
     */
    protected $skipTrial;

    /**
     * StripeSubscriptionBuilder constructor.
     *
     * @param $owner
     * @param PricingPlan $pricingPlan
     */
    public function __construct($owner, PricingPlan $pricingPlan)
    {
        $this->owner = $owner;
        $this->pricingPlan = $pricingPlan;
        $this->skipTrial = $this->pricingPlan->trial_days === 0;
    }

    /**
     * @param null $token
     * @param array $options
     * @return Subscription
     */
    public function create($token = null, array $options = []): Subscription
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
            'pricing_plan_id' => $this->pricingPlan->id,
            'stripe_subscription_id' => $subscription->id,
            'type' => $this->pricingPlan->planAsString(),
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
        return Carbon::now()->addDays($this->pricingPlan->trial_days);
    }

    /**
     * @return array
     */
    protected function getSubscriptionOptions(): array
    {
        return array_filter([
            'plan' => $this->pricingPlan->stripe_plan_id,
            'trial_end' => $this->getTrialEnd(),
        ]);
    }
}