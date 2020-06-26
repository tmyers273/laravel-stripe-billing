<?php

namespace TMyers\StripeBilling\Services;

use App\User;
use TMyers\StripeBilling\BillableContract;
use TMyers\StripeBilling\Models\Card;

class UserCardsManager
{
    /**
     * @param BillableContract $user
     * @param Card $card
     */
    public function removeCard(BillableContract $user, Card $card) {
        $user->removeCard($card);
    }

    /**
     * @param BillableContract $user
     * @param Card $card
     */
    public function setCardAsDefault(BillableContract $user, Card $card) {
        $user->setCardAsDefault($card);
    }

    /**
     * @param BillableContract $user
     * @param string $token
     * @return Card
     */
    public function addCardFromToken(BillableContract $user, string $token): Card {
        return $user->addCardFromToken($token);
    }
}
