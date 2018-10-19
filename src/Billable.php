<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 19.10.2018
 * Time: 18:55
 */

namespace TMyers\StripeBilling;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Models\Card;

trait Billable
{
    use HasSubscriptions;

    /**
     * @param null $token
     * @param array $options
     * @return Customer
     */
    public function retrieveOrCreateStripeCustomer($token = null, array $options = [])
    {
        if (! $this->stripe_id) {
            $customer = StripeCustomer::create($token, $this->email, $options);

            $this->update([
                'stripe_id' => $customer->id,
            ]);

            $this->addNewDefaultCard(
                StripeCustomer::parseDefaultCard($customer)
            );

            return $customer;
        }

        return StripeCustomer::retrieve($this->stripe_id);
    }

    /**
     * @param string $stripeCardId
     * @param string $brand
     * @param string $last4
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