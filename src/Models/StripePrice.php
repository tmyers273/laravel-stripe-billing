<?php

namespace TMyers\StripeBilling\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\Exceptions\StripeBillingException;
use TMyers\StripeBilling\StripeBilling;

/**
 * Class Price
 *
 * @package TMyers\StripeBilling\Models
 *
 * @property StripeProduct $product
 * @property integer $id
 * @property int $stripe_price_id
 * @property int $trial_days
 * @property boolean $active
 * @property string $name
 * @property string $description
 * @property string $interval
 * @property integer $price
 */
class StripePrice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'product_id' => 'integer',
    ];

    /**
     * @param string $name
     * @return StripePrice
     */
    public static function findByName(string $name): self {
        return static::whereName($name)->firstOrFail();
    }

    /**
     * @return bool
     */
    public function isActive(): bool {
        return !! $this->active;
    }

    public function hasSameTypeAs($price) {
        if (is_null($this->product_id)) {
            return false;
        }

        return (int) $this->product_id === (int) $price->product_id;
    }

    /**
     * @param StripePrice $price
     * @return bool
     * @throws StripeBillingException
     */
    public function isGreaterThan($price): bool {
        if (!is_a($price, StripeBilling::getPricesModel())) {
            throw new StripeBillingException("Only pricing plans are allowed for comparison");
        }

        return $this->price > $price->price;
    }

    /**
     * @param StripePrice $price
     * @return bool
     * @throws StripeBillingException
     */
    public function isLessThan($price): bool {
        if (!is_a($price, StripeBilling::getPricesModel())) {
            throw new StripeBillingException("Only pricing plans are allowed for comparison");
        }

        return $this->price < $price->price;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $builder) {
        return $builder->whereActive(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function subscriptions() {
        return $this->hasMany(StripeBilling::getSubscriptionModel());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(StripeBilling::getProductModel());
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->product ? $this->product->name : 'default';
    }
}
