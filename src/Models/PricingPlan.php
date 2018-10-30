<?php

namespace TMyers\StripeBilling\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\StripeBilling;

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
        'plan_id' => 'integer',
    ];

    /**
     * @param string $name
     * @return PricingPlan
     */
    public static function findByName(string $name): self
    {
        return static::whereName($name)->firstOrFail();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return !! $this->active;
    }

    public function hasSameTypeAs($plan)
    {
        if (is_null($this->plan_id)) {
            return false;
        }

        return (int) $this->plan->id === (int) $plan->plan_id;
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
        return $this->hasMany(
            StripeBilling::getSubscriptionModel()
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(
            StripeBilling::getPlanModel()
        );
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->plan ? $this->plan->name : 'default';
    }
}