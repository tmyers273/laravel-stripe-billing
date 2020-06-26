<?php

namespace TMyers\StripeBilling\Services;

use App\User;
use TMyers\StripeBilling\Billable;
use TMyers\StripeBilling\Models\Card;

class UserCardsManager
{
    /**
     * @param Billable $user
     * @param Card $card
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function removeCard(Billable $user, Card $card) {
        $user->removeCard($card);
    }

    /**
     * @param Billable $user
     * @param Card $card
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function setCardAsDefault(Billable $user, Card $card) {
        $user->setCardAsDefault($card);
    }

    /**
     * @param Billable $user
     * @param string $token
     * @return Card
     * @throws \TMyers\StripeBilling\Exceptions\CardException
     */
    public function addCardFromToken(Billable $user, string $token): Card {
        return $user->addCardFromToken($token);
    }
}
