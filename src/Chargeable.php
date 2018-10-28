<?php

namespace TMyers\StripeBilling;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TMyers\StripeBilling\Exceptions\CardException;
use TMyers\StripeBilling\Facades\StripeCharge;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Models\Card;

trait Chargeable
{
    /**
     * @param array $data
     * @return Card
     */
    public function addNewDefaultCard(array $data)
    {
        $cardModel = StripeBilling::getCardModel();

        $card = $cardModel::create([
            'owner_id' => $this->id,
            'stripe_card_id' => $data['stripe_card_id'],
            'brand' => $data['brand'],
            'last_4' => $data['last_4'],
        ]);

        $this->forceFill([
            'default_card_id' => $card->id,
        ])->save();

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

        $stripeCustomer = $this->retrieveStripeCustomer();

        if (StripeCustomer::isDefaultSource($stripeCustomer, $token)) {
            return;
        }

        $stripeCard = StripeCustomer::createSource($stripeCustomer, $token);

        $cardModel = StripeBilling::getCardModel();

        $card = $cardModel::create([
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
        if (!$card->isOwnedBy($this)) {
            throw new CardException("Card does not belong to that owner.");
        }

        $stripeCustomer = $this->retrieveStripeCustomer();

        $stripeCustomer->default_source = $card->stripe_card_id;
        $stripeCustomer->save();

        $this->forceFill([
            'default_card_id' => $card->id,
        ])->save();
    }

    /**
     * @param $card
     * @throws CardException
     */
    public function removeCard($card)
    {
        if (!is_a($card, StripeBilling::getCardModel())) {
            throw CardException::wrongType($card, StripeBilling::getCardModel());
        }

        if (!$card->isOwnedBy($this)) {
            throw CardException::notOwnedBy($this);
        }

        $stripeCustomer = $this->retrieveStripeCustomer();

        StripeCustomer::deleteSource($stripeCustomer, $card->stripe_card_id);

        if ($stripeCustomer->default_source === $card->id) {
            $stripeCustomer->default_source = null;
            $stripeCustomer->save();
        }

        if ($this->hasDefaultCard($card)) {
            $this->forceFill([
                'default_card_id' => null,
            ])->save();
        }

        $card->delete();
    }

    /**
     * @param Card|null $card
     * @return bool
     */
    public function hasDefaultCard($card = null): bool
    {
        if ($card) {
            return is_a($card, StripeBilling::getCardModel()) &&
                (int) $this->default_card_id === $card->id;
        }

        return ! is_null($this->default_card_id);
    }

    /**
     * @return string
     */
    protected function getCardClass(): string
    {
        return config('stripe-billing.models.card');
    }

    /*
    |--------------------------------------------------------------------------
    | Single charges
    |--------------------------------------------------------------------------
    */

    /**
     * @param int $amount
     * @param array $params
     * @return mixed
     */
    public function charge(int $amount, array $params = [])
    {
        $params = array_merge($params, [
            'currency' => StripeBilling::getCurrency(),
        ]);

        $params['amount'] = $amount;

        if (!array_key_exists('source', $params) && $this->stripe_id) {
            $params['customer'] = $this->stripe_id;
        }

        if (! array_key_exists('source', $params) && ! array_key_exists('customer', $params)) {
            throw new \InvalidArgumentException('No payment source provided.');
        }

        return StripeCharge::charge($params);
    }

    /**
     * @param int $amount
     * @param $card
     * @param array $params
     * @return mixed
     */
    public function chargeCard(int $amount, $card, array $params = [])
    {
        if (is_a($card, StripeBilling::getCardModel())) {
            $params['customer'] = $card->owner->stripe_id;
            $params['source'] = $card->stripe_card_id;
        } else {
            throw CardException::wrongType($card);
        }

        return $this->charge($amount, $params);
    }

    /**
     * @param int $amount
     * @param string $token
     * @param array $params
     * @return mixed
     */
    public function chargeByToken(int $amount, string $token, array $params = [])
    {
        return $this->charge($amount, array_merge($params, ['source' => $token]));
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
        return $this->hasMany(StripeBilling::getCardModel(), 'owner_id');
    }

    /**
     * @return BelongsTo
     */
    public function defaultCard()
    {
        return $this->belongsTo(
            StripeBilling::getCardModel(),
            'default_card_id'
        );
    }
}