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
        return $this->belongsTo(config('stripe-billing.models.user'), 'user_id');
    }
}