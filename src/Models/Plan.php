<?php

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'trial_days' => 'integer'
    ];

    public static function boot()
    {
        static::creating(function($user) {
            if (empty($user->code_name)) {
                $user->code_name = str_slug($user->name);
            }
        });
    }

    /**
     * @param string $codeName
     * @return Plan
     */
    public static function fromCodeName(string $codeName): self
    {
        return static::whereCodeName($codeName)->firstOrFail();
    }
    
    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $builder)
    {
        return $builder->whereActive(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function subscriptions()
    {
        return $this->hasMany(config('stripe-billing.models.subscription'));
    }

    public function planType()
    {
        return $this->belongsTo(config('stripe-billing.models.plan_type'));
    }
}