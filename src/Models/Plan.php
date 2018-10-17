<?php

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['id'];

    public static function boot()
    {
        static::creating(function($user) {
            if (empty($user->slug)) {
                $user->slug = str_slug($user->name);
            }
        });
    }

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
}