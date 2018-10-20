<?php

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Plan
 * @package TMyers\StripeBilling\Models
 * @property int $stripe_plan_id
 * @property boolean $active
 * @property string $name
 * @property string $code_name
 */
class Plan extends Model
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
     * @return Plan
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

    public function planType()
    {
        return $this->belongsTo(config('stripe-billing.models.plan_type'));
    }

    public function planTypeAsString(): string
    {
        return $this->planType ? $this->planType->code_name : 'default';
    }
}