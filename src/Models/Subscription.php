<?php

namespace TMyers\StripeBilling\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\PriceIsInactive;
use TMyers\StripeBilling\Exceptions\StripeBillingException;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\StripeBilling;

/**
 * Class Subscription
 *
 * @package TMyers\StripeBilling\Models
 * @property Price $price
 * @property string $stripe_subscription_id
 * @property Carbon $trial_ends_at
 * @property Carbon $ends_at
 * @property integer $owner_id
 * @property integer $price_id
 * @property mixed $user
 */
class Subscription extends Model
{
    protected $guarded = ['id'];

    protected $with = ['price', 'price.product'];

    protected $casts = [
        'price_id' => 'integer',
        'owner_id' => 'integer',
    ];

    protected $dates = [
        'trial_ends_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Indicates if the plan change should be prorated.
     *
     * @var bool
     */
    protected $prorate = true;

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    public function cancelAtPeriodEnd(): self {
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->cancel_at_period_end = true;

        $stripeSubscription->save();

        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } else {
            $this->ends_at = StripeSubscription::parseCurrentPeriodEnd($stripeSubscription);
        }

        $this->save();

        return $this;
    }

    public function cancelNow(): self {
        /** @var \Stripe\Subscription $stripeSubscription */
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->cancel();

        $this->markAsCanceled();

        return $this;
    }

    public function markAsCanceled() {
        $this->fill(['ends_at' => Carbon::now()])->save();
    }

    public function trialEndAt(int $unixTimestamp, bool $prorate = false) {
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->trial_end = $unixTimestamp;
        $stripeSubscription->prorate = $prorate;

        $stripeSubscription->save();

        $this->update([
            'trial_ends_at' => $unixTimestamp,
        ]);
    }

    public function addDaysToTrial(int $days, bool $prorate = false) {
        $this->trialEndAt((clone $this->trial_ends_at)->addDays($days)->getTimestamp(), $prorate);
    }

    /*
    |--------------------------------------------------------------------------
    | Swapping plans
    |--------------------------------------------------------------------------
    */

    /**
     * @param $price
     * @return $this
     * @throws PriceIsInactive
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function changeTo($price): self {
        if ( ! is_a($price, StripeBilling::getPricesModel()) ) {
            throw new \InvalidArgumentException(
                "Plan must be an instance of " . StripeBilling::getPricesModel()
            );
        }

        if ($this->isStrictlyFor($price)) {
            throw AlreadySubscribed::toPrice($price);
        }

        if ( ! $price->isActive()) {
            throw PriceIsInactive::price($price);
        }

        /** @var \Stripe\Subscription $stripeSubscription */
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->plan = $price->stripe_product_id;

        if ($this->onTrial()) {
            $stripeSubscription->trial_end = $this->trial_ends_at->getTimestamp();
        } else {
            $stripeSubscription->trial_end = 'now';
        }

        $stripeSubscription->prorate = $this->prorate;

        $stripeSubscription->save();

        $this->update([
            'price_id' => $price->id,
            'type' => $price->getType(),
        ]);

        return $this;
    }

    /**
     * @throws StripeBillingException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function resume(): self
    {
        if (!$this->onGracePeriod()) {
            throw new StripeBillingException(
                "Subscription for plan {$this->price->name} is not on grace period"
            );
        }

        if (!$this->price->isActive()) {
            throw PriceIsInactive::price($this->price);
        }

        /** @var \Stripe\Subscription $stripeSubscription */
        $stripeSubscription = StripeSubscription::retrieve($this->stripe_subscription_id);

        $stripeSubscription->cancel_at_period_end = false;
        $stripeSubscription->plan = $this->price->stripe_product_id;

        if ($this->onTrial()) {
            $stripeSubscription->trial_end = $this->trial_ends_at->getTimestamp();
        } else {
            $stripeSubscription->trial_end = 'now';
        }

        $stripeSubscription->save();

        $this->fill(['ends_at' => null])->save();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * @param string|Price $price
     * @return bool
     */
    public function isStrictlyFor($price): bool
    {
        if ( is_string($price))  {
            return $this->price->name === $price;
        }

        if ( is_a($price, StripeBilling::getPricesModel()) ) {
            return $this->price_id === $price->id;
        }

        return false;
    }

    /**
     * @param string|Price|Product $plan
     * @return bool
     */
    public function isFor($plan): bool {
        if (!$this->isActive()) {
            return false;
        }

        if (is_string($plan)) {
            return $this->price->name === $plan || $this->price->getType() === $plan;
        }

        if ( is_a($plan, StripeBilling::getPricesModel()) ) {
            return $this->price->is($plan) || $this->price->hasSameTypeAs($plan);
        }

        if ( is_a($plan, StripeBilling::getProductModel()) ) {
            return $plan->is($this->price->plan);
        }

        return false;
    }

    public function cancelled()
    {
        return ! is_null($this->ends_at);
    }

    public function ended()
    {
        return $this->cancelled() && ! $this->onGracePeriod();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return is_null($this->ends_at) || $this->ends_at->gt(now());
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * @return int
     */
    public function daysUntilTheEndOfTheGracePeriod(): int
    {
        if ($this->onGracePeriod()) {
            return $this->ends_at->diffInDays(now());
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive()
    {
        return $this->whereNull('ends_at')->orWhere('ends_at', '>', now());
    }

    public function scopeCanceledAndArchived()
    {
        return $this->whereNotNull('ends_at')->where('ends_at', '<', now());
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function price() {
        return $this->belongsTo(StripeBilling::getPricesModel(), 'price_id');
    }

    public function owner() {
        return $this->belongsTo(StripeBilling::getOwnerModel(), 'owner_id');
    }
}
