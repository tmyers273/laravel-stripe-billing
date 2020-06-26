<?php

namespace TMyers\StripeBilling\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TMyers\StripeBilling\BillableContract;
use TMyers\StripeBilling\Exceptions\CardException;
use TMyers\StripeBilling\Http\Requests\AddNewCardRequest;
use TMyers\StripeBilling\Models\Card;
use TMyers\StripeBilling\Services\UserCardsManager;

class CardsController extends Controller
{
    /** @var UserCardsManager */
    private $ucm;

    /**
     * CardsController constructor.
     * @param UserCardsManager $ucm
     */
    public function __construct(UserCardsManager $ucm) {
        $this->ucm = $ucm;
    }

    public function index(Request $request) {
        /** @var BillableContract $user */
        $user = $request->user();

        $cards = $user->cards()->with('owner')->get();

        return response()->json($cards);
    }

    /**
     * @param AddNewCardRequest $request
     * @return JsonResponse
     */
    public function store(AddNewCardRequest $request) {
        /** @var BillableContract $user */
        $user = $request->user();

        try {
            $card = $this->ucm->addCardFromToken($user, $request->stripeToken);
        } catch (CardException $e) {
            return response()->json($e->getMessage(), 400);
        }

        return response()->json($card);
    }

    /**
     * @param Card $card
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function default(Card $card, Request $request) {
        /** @var BillableContract $user */
        $user = $request->user();

        try {
            $this->ucm->setCardAsDefault($user, $card);
        } catch (CardException $e) {
            return response()->json($e->getMessage(), 400);
        }

        return response()->json('ok', 200);
    }

    /**
     * @param Card $card
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Card $card, Request $request) {
        /** @var BillableContract $user */
        $user = $request->user();

        try {
            $this->ucm->removeCard($user, $card);
        } catch (CardException $e) {
            return response()->json($e->getMessage(), 400);
        }

        return response()->json(null, 204);
    }
}
