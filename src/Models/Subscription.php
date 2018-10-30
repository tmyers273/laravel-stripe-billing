<?php

namespace TMyers\StripeBilling\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\PlanIsInactive;
use TMyers\StripeBilling\Exceptions\StripeBillingException;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\StripeBilling;

/**
 * Class Subscription
 *
 * @package TMyers\StripeBilling\Models
 * @property PricingPlan $pricingPlan
 * @property string $stripe_subscription_id
 * @property Carbon $trial_ends_at
 * @property Carbon $ends_at
 * @property integer $owner_id
 * @property integer $pricing_plan_id
 */
class Subscription extends Model
{
    protected $guarded = ['id'];

    protected $with = ['pricingPlan', 'pricingPlan.plan'];

    protected $casts = [
        'pricing_plan_id' => 'integer',
        'owner_id' => 'integer',
    ];

    protected $dates = [
        'trial_ends_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Indicates if the plan change should be prorated.
     *
     * @var bool
     */
    protected $prorate = true;

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    public function cancelAtPeriodEnd(): self
    {
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->cancel_at_period_end = true;

        $stripeSubscription->save();

        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } else {
            $this->ends_at = StripeSubscription::parseCurrentPeriodEnd($stripeSubscription);
        }

        $this->save();

        return $this;
    }

    public function cancelNow(): self
    {
        /** @var \Stripe\Subscription $stripeSubscription */
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->cancel();

        $this->markAsCanceled();

        return $this;
    }

    public function markAsCanceled()
    {
        $this->fill(['ends_at' => Carbon::now()])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Swapping plans
    |--------------------------------------------------------------------------
    */

    /**
     * @param PricingPlan $plan
     * @return $this
     * @throws AlreadySubscribed
     * @throws PlanIsInactive
     */
    public function changeTo($plan): self
    {
        if ( ! is_a($plan, StripeBilling::getPricingPlanModel()) ) {
            throw new \InvalidArgumentException(
                "Plan must be an instance of " . StripeBilling::getPricingPlanModel()
            );
        }

        if ($this->isFor($plan)) {
            throw AlreadySubscribed::toPlan($plan);
        }

        if ( ! $plan->isActive()) {
            throw PlanIsInactive::plan($plan);
        }

        /** @var \Stripe\Subscription $stripeSubscription */
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->plan = $plan->stripe_plan_id;

        if ($this->onTrial()) {
            $stripeSubscription->trial_end = $this->trial_ends_at->getTimestamp();
        } else {
            $stripeSubscription->trial_end = 'now';
        }

        $stripeSubscription->prorate = $this->prorate;

        $stripeSubscription->save();

        $this->update([
            'pricing_plan_id' => $plan->id,
            'type' => $plan->getType(),
        ]);

        return $this;
    }

    /**
     * @throws StripeBillingException
     */
    public function resume(): self
    {
        if (!$this->onGracePeriod()) {
            throw new StripeBillingException(
                "Subscription for plan {$this->pricingPlan->name} is not on grace period"
            );
        }

        if (!$this->pricingPlan->isActive()) {
            throw PlanIsInactive::plan($this->pricingPlan);
        }

        /** @var \Stripe\Subscription $stripeSubscription */
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->cancel_at_period_end = false;
        $stripeSubscription->plan = $this->pricingPlan->stripe_plan_id;

        if ($this->onTrial()) {
            $stripeSubscription->trial_end = $this->trial_ends_at->getTimestamp();
        } else {
            $stripeSubscription->trial_end = 'now';
        }

        $stripeSubscription->save();

        $this->fill(['ends_at' => null])->save();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * @param string|PricingPlan $pricingPlan
     * @return bool
     */
    public function isStrictlyFor($pricingPlan): bool
    {
        if ( is_string($pricingPlan))  {
            return $this->pricingPlan->name === $pricingPlan;
        }

        if ( is_a($pricingPlan, StripeBilling::getPricingPlanModel()) ) {
            return $this->pricing_plan_id === $pricingPlan->id;
        }

        return false;
    }

    /**
     * @param string|PricingPlan|Plan $plan
     * @return bool
     */
    public function isFor($plan): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (is_string($plan)) {
            return $this->pricingPlan->name === $plan || $this->pricingPlan->getType() === $plan;
        }

        if ( is_a($plan, StripeBilling::getPricingPlanModel()) ) {
            return $this->pricingPlan->is($plan) || $this->pricingPlan->hasSameTypeAs($plan);
        }

        if ( is_a($plan, StripeBilling::getPlanModel()) ) {
            return $plan->is($this->pricingPlan->plan);
        }

        return false;
    }

    public function cancelled()
    {
        return ! is_null($this->ends_at);
    }

    public function ended()
    {
        return $this->cancelled() && ! $this->onGracePeriod();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return is_null($this->ends_at) || $this->ends_at->gt(now());
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * @return int
     */
    public function daysUntilTheEndOfTheGracePeriod(): int
    {
        if ($this->onGracePeriod()) {
            return $this->ends_at->diffInDays(now());
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive()
    {
        return $this->whereNull('ends_at')->orWhere('ends_at', '>', now());
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function pricingPlan()
    {
        return $this->belongsTo(StripeBilling::getPricingPlanModel(), 'pricing_plan_id');
    }

    public function user()
    {
        return $this->belongsTo(StripeBilling::getOwnerModel(), 'owner_id');
    }
}