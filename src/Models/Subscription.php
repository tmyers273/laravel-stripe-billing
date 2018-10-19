<?php

namespace TMyers\StripeBilling\Models;

use Illuminate\Database\Eloquent\Model;

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