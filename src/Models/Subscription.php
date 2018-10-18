<?php

namespace TMyers\StripeBilling\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = ['id'];

    protected $with = ['plan'];

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
            return $this->plan->code === $plan;
        }

        if ($plan instanceof Plan) {
            return $this->plan->is($plan);
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