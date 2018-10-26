<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 19.10.2018
 * Time: 19:52
 */

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class Card
 *
 * @package TMyers\StripeBilling\Models
 *
 * @property integer $owner_id
 * @property string $brand
 * @property string $last_4
 * @property string $stripe_card_id
 */
class Card extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'owner_id' => 'integer'
    ];

    /**
     * @param $owner
     * @return bool
     */
    public function isOwnedBy($owner): bool
    {
        if (!is_a($owner, config('stripe-billing.models.owner'), true)) {
            return false;
        }

        return (int) $owner->id === (int) $this->owner_id;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->owner->hasDefaultCard($this);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(config('stripe-billing.models.owner'), 'owner_id');
    }
}