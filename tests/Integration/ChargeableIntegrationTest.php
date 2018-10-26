<?php

namespace TMyers\StripeBilling\Tests\Integration;


use Carbon\Carbon;
use TMyers\StripeBilling\Exceptions\CardException;
use TMyers\StripeBilling\Models\Card;
use TMyers\StripeBilling\Tests\Stubs\Models\User;
use TMyers\StripeBilling\Tests\TestCase;

class ChargeableIntegrationTest extends TestCase
{
    public function setUp()
    {
        if (!env('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Integration tests are being skipped. See phpunit.xml');
        }

        parent::setUp();

        Carbon::setTestNow(now()->addMinutes(5));
    }

    protected function tearDown()
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @test
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function a_card_can_be_added_to_user_and_become_default()
    {
        // Given we have a user without any card
        $user = $this->createUser();

        $user->addCardFromToken($this->createTestToken());

        tap($user->fresh(), function(User $user) {
            $this->assertCount(1, $user->cards);

            $this->assertNotNull($user->stripe_id);
            $this->assertNotNull($user->default_card_id);
            $this->assertTrue($user->hasDefaultCard());

            $card = $user->defaultCard;
            $this->assertInstanceOf(Card::class, $card);
            $this->assertEquals('Visa', $card->brand);
            $this->assertEquals(4242, $card->last_4);
            $this->assertNotNull($card->stripe_card_id);
        });
    }

    /**
     * @test
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function user_can_add_a_card_already_having_a_default_one()
    {
        // Given we have a user without any card
        $user = $this->createUser();
        $stripeId = null;
        $defaultCardId = null;

        $user->addCardFromToken($this->createTestToken());

        tap($user->fresh(), function(User $user) use (&$stripeId, &$defaultCardId) {
            $stripeId = $user->stripe_id;
            $defaultCardId = $user->default_card_id;
        });

        $user->addCardFromToken($this->createTestToken());

        tap($user->fresh(), function(User $user) use ($stripeId, $defaultCardId) {
             $this->assertCount(2, $user->cards);

             $this->assertEquals($stripeId, $user->stripe_id);
             $this->assertEquals($defaultCardId, $user->default_card_id);

             foreach ($user->cards as $card) {
                 $this->assertInstanceOf(Card::class, $card);
                 $this->assertEquals('Visa', $card->brand);
                 $this->assertEquals(4242, $card->last_4);
                 $this->assertNotNull($card->stripe_card_id);
             }

             $this->assertNotEquals(
                 $user->cards[0]->stripe_card_id,
                 $user->cards[1]->stripe_card_id
             );
        });
    }

    /**
     * @test
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function two_cards_can_be_swapped()
    {
        // Given we have a user without any card
        $user = $this->createUser();

        $defaultCard = $user->addCardFromToken($this->createTestToken());
        $anotherCard = $user->addCardFromToken($this->createTestToken());

        $user->setCardAsDefault($anotherCard);

        tap($user->fresh(), function(User $user) use ($anotherCard, $defaultCard) {
            $this->assertNotNull($user->default_card_id);
            $this->assertTrue($user->hasDefaultCard());

            $this->assertTrue($user->defaultCard->is($anotherCard));
            $this->assertFalse($user->defaultCard->is($defaultCard));
            $this->assertCount(2, $user->cards);
        });
    }

    /**
     * @test
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function it_will_throw_on_wrong_card_for_a_wrong_user()
    {
        // Given we have 2 different users each with a card
        $firstUser = $this->createUser();
        $anotherUser = $this->createUser();

        $firstCard = $firstUser->addCardFromToken($this->createTestToken());
        $anotherCard = $anotherUser->addCardFromToken($this->createTestToken());

        // First card belongs to the first user
        $this->assertTrue($firstCard->isOwnedBy($firstUser));
        $this->assertFalse($firstCard->isOwnedBy($anotherUser));

        // Second card belongs to the second user
        $this->assertTrue($anotherCard->isOwnedBy($anotherUser));
        $this->assertFalse($anotherCard->isOwnedBy($firstUser));

        // Expect card exception
        $this->expectException(CardException::class);

        // Do try giving one user a card of another user
        $firstUser->setCardAsDefault($anotherCard);
    }
}