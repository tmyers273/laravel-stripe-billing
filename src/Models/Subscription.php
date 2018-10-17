<?php

namespace TMyers\StripeBilling\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = ['id'];

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