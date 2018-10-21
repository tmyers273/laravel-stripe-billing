<?php

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Plan
 *
 * @package TMyers\StripeBilling\Models
 *
 * @property string $name
 * @property string $string
 * @property boolean $active
 * @property integer $id
 */
class Plan extends Model
{
    protected $guarded = ['id'];

    /**
     * @param string $name
     * @return Plan
     */
    public static function findByName(string $name): self
    {
        return static::whereName($name)->firstOrFail();
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
            config('stripe-billing.models.pricing_plan')
        );
    }
}