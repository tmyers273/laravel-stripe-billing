<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use TMyers\StripeBilling\Models\Card;
use TMyers\StripeBilling\Tests\Stubs\Models\User;

trait UserAndCardFactory {
    /**
     * @param array $overrides
     * @return User
     */
    protected function createUser(array $overrides = []): User {
        return User::create(array_merge([
            'name' => 'Denis',
            'email' => 'denis.mitr@gmail.com'
        ], $overrides));
    }

    /**
     * @param array $userOverrides
     * @param array $cardOverrides
     * @return array
     */
    protected function createUserWithDefaultCard(array $userOverrides = [], array $cardOverrides = []): array {
        $user = User::create(array_merge([
            'name' => 'Denis',
            'email' => 'denis.mitr@gmail.com',
            'stripe_id' => 'fake-stripe-id',
        ], $userOverrides));

        $card = Card::create(array_merge([
            'owner_id' => $user->id,
            'brand' => 'Visa',
            'last_4' => '4242',
            'stripe_card_id' => 'fake-card-id',
        ], $cardOverrides));

        return [$user, $card];
    }

    /**
     * @param User $user
     * @param array $overrides
     * @return mixed
     */
    protected function createCardForUser(User $user, array $overrides = []) {
        return Card::create(array_merge([
            'owner_id' => $user->id,
            'brand' => 'Visa',
            'last_4' => '4242',
            'stripe_card_id' => 'fake-card-id',
        ], $overrides));
    }
}
