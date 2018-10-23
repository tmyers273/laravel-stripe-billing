<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 18.10.2018
 * Time: 16:19
 */

namespace TMyers\StripeBilling;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TMyers\StripeBilling\Exceptions\CardException;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Facades\StripeToken;
use TMyers\StripeBilling\Models\Card;

trait Chargeable
{
    /**
     * @param array $data
     * @return Card
     */
    public function addNewDefaultCard(array $data): Card
    {
        $card = Card::create([
            'owner_id' => $this->id,
            'stripe_card_id' => $data['stripe_card_id'],
            'brand' => $data['brand'],
            'last_4' => $data['last_4'],
        ]);

        $this->update([
            'default_card_id' => $card->id,
        ]);

        return $card;
    }

    /**
     * @param $token
     * @return Card
     * @throws CardException
     */
    public function addCard($token)
    {
        $stripeCustomer = StripeCustomer::retrieve($this->stripe_id);
        $stripeToken = StripeToken::retrieve($token);

        // If the given token already has the card as their default source, we can just
        // bail out of the method now. We don't need to keep adding the same card to
        // a model's account every time we go through this particular method call.
        if ($stripeToken[$stripeToken->type]->id === $stripeCustomer->default_source) {
            return;
        }

        $stripeCard = $stripeToken->sources->create(['source' => $token]);

        $card = Card::create([
            'owner_id' => $this->id,
            'stripe_card_id' => $stripeCard->id,
            'brand' => $stripeCard->brand,
            'last_4' => $stripeCard->last4,
        ]);

        if (!$this->default_card_id) {
            $this->setDefaultCard($card);
        }

        return $card;
    }

    /**
     * @param $card
     * @throws CardException
     */
    public function setDefaultCard($card)
    {
        if ($card->owner_id !== $this->id) {
            throw new CardException("Card does not belong to that owner.");
        }

        $stripeCustomer = StripeCustomer::retrieve($this->stripe_id);

        $stripeCustomer->default_source = $card->stripe_card_id;
        $stripeCustomer->save();

        $this->forceFill([
            'default_card_id' => $card->id,
        ])->save();
    }
    
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return mixed
     */
    public function cards()
    {
        return $this->hasMany(config('stripe-billing.models.card'), 'owner_id');
    }

    /**
     * @return BelongsTo
     */
    public function defaultCard()
    {
        return $this->belongsTo(
            config('stripe-billing.models.card'),
            'default_card_id',
            'owner_id'
        );
    }
}