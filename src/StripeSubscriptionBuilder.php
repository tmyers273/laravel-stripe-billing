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
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Subscription;

class StripeSubscriptionBuilder
{
    protected $owner;

    /**
     * @var Plan
     */
    protected $plan;

    /**
     * @var bool
     */
    protected $skipTrial;

    /**
     * StripeSubscriptionBuilder constructor.
     *
     * @param $owner
     * @param Plan $plan
     */
    public function __construct($owner, Plan $plan)
    {
        $this->owner = $owner;
        $this->plan = $plan;
        $this->skipTrial = $this->plan->trial_days === 0;
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
            'plan_id' => $this->plan->id,
            'stripe_subscription_id' => $subscription->id,
            'type' => $this->plan->planTypeAsString(),
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
        return Carbon::now()->addDays($this->plan->trial_days);
    }

    /**
     * @return array
     */
    protected function getSubscriptionOptions(): array
    {
        return array_filter([
            'plan' => $this->plan->stripe_plan_id,
            'trial_end' => $this->getTrialEnd(),
        ]);
    }
}