<?php

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Plan
 * @package TMyers\StripeBilling\Models
 */
class Plan extends Model
{
    protected $guarded = ['id'];

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
        return $this->hasManyThrough(
            config('stripe-billing.models.subscription'),
            config('stripe-billing.models.plan')
        );
    }
}