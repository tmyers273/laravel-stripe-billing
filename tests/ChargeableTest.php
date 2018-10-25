<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 25.10.2018
 * Time: 16:15
 */

namespace TMyers\StripeBilling\Tests;


use TMyers\StripeBilling\Facades\StripeCustomer;

class ChargeableTest extends TestCase
{
    const FAKE_CUSTOMER_100 = 'fake-customer-100';

    /**
     * @test
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function a_card_can_be_added_to_user()
    {
        $user = $this->createUser();
        $token = $this->createTestToken();

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

        $card = $user->addCard($token);

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
}