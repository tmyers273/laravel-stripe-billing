<?php

namespace TMyers\StripeBilling\Models;


use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\StripeBilling;

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
        if (!is_a($owner, StripeBilling::getOwnerModel())) {
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
        return $this->belongsTo(StripeBilling::getOwnerModel(), 'owner_id');
    }
}