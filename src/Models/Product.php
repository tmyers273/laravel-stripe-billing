<?php

namespace TMyers\StripeBilling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use TMyers\StripeBilling\StripeBilling;

/**
 * Class Product
 *
 * @package TMyers\StripeBilling\Models
 *
 * @property string $name
 * @property string $string
 * @property boolean $active
 * @property integer $id
 * @property integer $weight
 * @property Collection $prices
 * @property Collection $subscriptions
 */
class Product extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['weight' => 'integer'];

    /**
     * @param string $name
     * @return Product
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function subscriptions()
    {
        return $this->hasManyThrough(
            StripeBilling::getSubscriptionModel(),
            StripeBilling::getPricesModel()
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this
            ->hasMany(StripeBilling::getPricesModel(), 'product_id')
            ->orderBy('price');
    }
}
