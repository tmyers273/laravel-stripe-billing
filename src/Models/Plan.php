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
            if (empty($user->code)) {
                $user->code = str_slug($user->name);
            }
        });
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

    public function scopeForIndividualUsers(Builder $builder)
    {
        return $builder->whereTeamsEnabled(false);
    }

    public function scopeForTeams(Builder $builder)
    {
        return $builder->whereTeamsEnabled(true);
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