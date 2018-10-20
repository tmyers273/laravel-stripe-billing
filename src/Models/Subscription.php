<?php

namespace TMyers\StripeBilling\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\PlanIsInactive;
use TMyers\StripeBilling\Facades\StripeSubscription;

class Subscription extends Model
{
    protected $guarded = ['id'];

    protected $with = ['plan', 'plan.planType'];

    protected $casts = [
        'plan_id' => 'integer',
        'user_id' => 'integer',
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
     * @param Plan $plan
     * @return $this
     * @throws AlreadySubscribed
     * @throws PlanIsInactive
     */
    public function changeTo(Plan $plan)
    {
        if ($this->isForPlan($plan)) {
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
            'plan_id' => $plan->id,
            'type' => $plan->planTypeAsString(),
        ]);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * @param string|Plan $plan
     * @return bool
     */
    public function isForPlan($plan): bool
    {
        if (is_string($plan)) {
            return $this->plan->code_name === $plan || optional($this->plan->planType)->code_name === $plan;
        }

        if ($plan instanceof Plan) {
            return $this->plan_id === $plan->id;
        }

        if ($plan instanceof PlanType) {
            return optional($this->plan->planType)->id === $plan->id;
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

    public function plan()
    {
        return $this->belongsTo(config('stripe-billing.models.plan'), 'plan_id');
    }

    public function user()
    {
        return $this->belongsTo(config('stripe-billing.models.owner'), 'owner_id');
    }
}