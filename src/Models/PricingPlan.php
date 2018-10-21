<?php

namespace TMyers\StripeBilling\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PricingPlan
 *
 * @package TMyers\StripeBilling\Models
 *
 * @property Plan $plan
 * @property integer $id
 * @property int $stripe_plan_id
 * @property boolean $active
 * @property string $name
 * @property string $description
 * @property string $interval
 * @property integer $price
 * @property integer $trial_days
 */
class PricingPlan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'trial_days' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * @param string $name
     * @return PricingPlan
     */
    public static function findByName(string $name): self
    {
        return static::whereName($name)->firstOrFail();
    }

    public function isActive(): bool
    {
        return !! $this->active;
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(config('stripe-billing.models.plan'));
    }

    /**
     * @return string
     */
    public function asPlanString(): string
    {
        return $this->plan ? $this->plan->name : 'default';
    }
}