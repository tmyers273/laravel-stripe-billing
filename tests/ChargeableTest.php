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
use Mockery as m;

class ChargeableTest extends TestCase
{
    const FAKE_CUSTOMER_100 = 'fake-customer-100';
    const FAKE_TOKEN_1 = 'fake-token-1';

    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

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
            $this->assertTrue($user->hasDefaultCard($card));
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
        list($user, $firstCard) = $this->createUserWithDefaultCard();
        $token = self::FAKE_TOKEN_1;

        // Mock
        StripeCustomer::shouldReceive('retrieve')->once()->with($user->stripe_id)
            ->andReturn($customer = $this->createCustomerObject(self::FAKE_CUSTOMER_100));

        StripeToken::shouldReceive('retrieve')->once()->with(self::FAKE_TOKEN_1)
            ->andReturn($stripeToken = $this->createTokenObject(self::FAKE_TOKEN_1, ['type' => "card"]));

        StripeToken::shouldReceive('isDefaultSource')->once()->with($stripeToken, $customer)->andReturn(false);

        StripeToken::shouldReceive('createSource')->once()->with($customer, $token)
            ->andReturn(new class extends Card {
                public $id = 'another-fake-card-id';
                public $brand = 'Master Card';
                public $last4 = '1111';
            });

        $customerMock = m::mock('Stripe\Customer[save]');
        $customerMock->shouldReceive('save')->once();

        StripeCustomer::shouldReceive('retrieve')->once()->with($user->stripe_id)
            ->andReturn($customerMock);

        $secondCard = $user->addCardFromToken($token);

        tap($user->fresh(), function(User $user) use ($secondCard, $firstCard) {
            $this->assertTrue($user->hasDefaultCard());
            $this->assertTrue($user->hasDefaultCard($secondCard));
            $this->assertFalse($user->hasDefaultCard($firstCard));
            $this->assertTrue($user->defaultCard->is($secondCard));
        });

        $this->assertDatabaseHas('cards', [
            'id' => $secondCard->id,
            'owner_id' => $user->id,
            'stripe_card_id' => 'another-fake-card-id',
            'brand' => 'Master Card',
            'last_4' => '1111',
        ]);
    }
    
    /** @test */
    public function user_with_different_cards_can_swap_them()
    {
        // Given we have a user with an active default card and another card
        list($user, $defaultCard) = $this->createUserWithDefaultCard();

        $anotherCard = $this->createCardForUser($user, [
            'brand' => 'Master Card',
            'last_4' => '1111',
            'stripe_card_id' => 'another-fake-card-id',
        ]);

        // Mock
        $customer = m::mock('Stripe\Card[save]');

        StripeCustomer::shouldReceive('retrieve')->once()->with($user->stripe_id)
            ->andReturn($customer);

        $customer->shouldReceive('save')->once();

        $user->setCardAsDefault($anotherCard);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'default_card_id' => $anotherCard->id,
        ]);

        tap($user->fresh(), function(User $user) use ($anotherCard, $defaultCard) {
            $this->assertTrue($user->hasDefaultCard());
            $this->assertTrue($user->hasDefaultCard($anotherCard));
            $this->assertFalse($user->hasDefaultCard($defaultCard));
            $this->assertTrue($user->defaultCard->is($anotherCard));
        });
    }
}