<?php

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use TMyers\StripeBilling\StripeBilling;

/**
 * Class Plan
 *
 * @package TMyers\StripeBilling\Models
 *
 * @property string $name
 * @property string $string
 * @property boolean $active
 * @property integer $id
 * @property integer $weight
 * @property Collection $pricingPlans
 * @property Collection $subscriptions
 */
class Plan extends Model {
    protected $guarded = ['id'];

    protected $casts = ['weight' => 'integer'];

    /**
     * @param string $name
     * @return Plan
     */
    public static function findByName(string $name): self {
        return static::whereName($name)->firstOrFail();
    }

    /**
     * @return bool
     */
    public function isFree(): bool {
        return ! ! $this->is_free;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $builder) {
        return $builder->whereActive(true);
    }

    public function scopeForIndividualUsers(Builder $builder) {
        return $builder->whereTeamsEnabled(false);
    }

    public function scopeForTeams(Builder $builder) {
        return $builder->whereTeamsEnabled(true);
    }

    public function scopeWeighted(Builder $builder) {
        return $builder->orderBy('weight');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function subscriptions() {
        return $this->hasManyThrough(
            StripeBilling::getSubscriptionModel(),
            StripeBilling::getPricingPlanModel()
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pricingPlans() {
        return $this
            ->hasMany(StripeBilling::getPricingPlanModel(), 'plan_id')
            ->orderBy('price');
    }
}
