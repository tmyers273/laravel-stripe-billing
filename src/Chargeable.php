<?php

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
    public function addCardFromToken(string $token)
    {
        if (!$this->stripe_id) {
            list($customer, $card) = $this->createCustomerWithDefaultCardFromToken($token);

            return $card;
        }

        $stripeCustomer = StripeCustomer::retrieve($this->stripe_id);
        $stripeToken = StripeToken::retrieve($token);

        if (StripeToken::isDefaultSource($stripeToken, $stripeCustomer)) {
            return;
        }

        $stripeCard = StripeToken::createSource($stripeCustomer, $token);

        $card = Card::create([
            'owner_id' => $this->id,
            'stripe_card_id' => $stripeCard->id,
            'brand' => $stripeCard->brand,
            'last_4' => $stripeCard->last4,
        ]);

        if (!$this->default_card_id) {
            $this->setCardAsDefault($card);
        }

        return $card;
    }

    /**
     * @param Card $card
     * @throws CardException
     */
    public function setCardAsDefault($card)
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

    public function hasDefaultCard(): bool
    {
        return ! is_null($this->default_card_id);
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
            'default_card_id'
        );
    }
}