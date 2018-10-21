<?php

namespace TMyers\StripeBilling\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PricingPlan
 * @package TMyers\StripeBilling\Models
 * @property Plan $plan
 * @property int $stripe_plan_id
 * @property boolean $active
 * @property string $name
 * @property string $code_name
 * @property integer $trial_days
 */
class PricingPlan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'trial_days' => 'integer',
        'active' => 'boolean',
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
     * @return PricingPlan
     */
    public static function fromCodeName(string $codeName): self
    {
        return static::whereCodeName($codeName)->firstOrFail();
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

    public function plan()
    {
        return $this->belongsTo(config('stripe-billing.models.plan'));
    }

    public function planAsString(): string
    {
        return $this->plan ? $this->plan->code_name : 'default';
    }
}