<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 25.10.2018
 * Time: 16:15
 */

namespace TMyers\StripeBilling\Tests;


use Stripe\Card;
use Stripe\Customer;
use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Facades\StripeToken;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

class ChargeableTest extends TestCase
{
    const FAKE_CUSTOMER_100 = 'fake-customer-100';
    const FAKE_TOKEN_1 = 'fake-token-1';

    /**
     * @test
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function a_card_can_be_added_to_user()
    {
        // Given we have a user without any card
        $user = $this->createUser();
        $token = self::FAKE_TOKEN_1;

        StripeCustomer::shouldReceive('create')
            ->once()
            ->with($token, $user->email, [])
            ->andReturn($customer = $this->createCustomerObject(self::FAKE_CUSTOMER_100));

        StripeCustomer::shouldReceive('parseDefaultCard')->once()->with($customer)
            ->andReturn([
                'stripe_card_id' => self::FAKE_CUSTOMER_100,
                'brand' => 'Visa',
                'last_4' => '4242',
            ]);

        $card = $user->addCardFromToken($token);

        tap($user->fresh(), function(User $user) use ($card) {
            $this->assertTrue($user->hasDefaultCard());
            $this->assertTrue($user->defaultCard->is($card));
        });

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'owner_id' => $user->id,
            'brand' => 'Visa',
            'last_4' => '4242',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'default_card_id' => $card->id,
            'stripe_id' => self::FAKE_CUSTOMER_100,
        ]);
    }

    /** @test */
    public function user_with_default_card_can_add_another_card()
    {
        // Given we have a user with an active default card
        list($user, $card) = $this->createUserWithDefaultCard();
        $token = self::FAKE_TOKEN_1;

        // Mock
        StripeCustomer::shouldReceive('retrieve')->once()->with($user->stripe_id)
            ->andReturn($customer = $this->createCustomerObject(self::FAKE_CUSTOMER_100));

        StripeToken::shouldReceive('retrieve')->once()->with(self::FAKE_TOKEN_1)
            ->andReturn($stripeToken = $this->createTokenObject(self::FAKE_TOKEN_1, ['type' => "card"]));

        StripeToken::shouldReceive('isDefaultSource')->once()->with($stripeToken, $customer)->andReturn(false);

        StripeToken::shouldReceive('createSource')->once()->with($customer, $token)
            ->andReturn(new class extends Card {
                public $id = 'fake-card-id';
                public $brand = 'Visa';
                public $last4 = '4242';
            });

        StripeCustomer::shouldReceive('retrieve')->once()->with($user->stripe_id)
            ->andReturn(new class extends Customer {
                public function save($opts = null) {}
            });

        $card = $user->addCardFromToken($token);

        tap($user->fresh(), function(User $user) use ($card) {
            $this->assertTrue($user->hasDefaultCard());
            $this->assertTrue($user->defaultCard->is($card));
        });

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'owner_id' => $user->id,
            'stripe_card_id' => 'fake-card-id',
        ]);
    }
}