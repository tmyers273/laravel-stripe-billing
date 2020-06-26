<?php

namespace TMyers\StripeBilling\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TMyers\StripeBilling\Billable;
use TMyers\StripeBilling\Models\StripeProduct;

class ProductsController extends Controller {

    public function index(Request $request) {
        /** @var Billable $user */
        $user = auth()->user();

        $plans = StripeProduct::with('prices')->active()->get();

        $subscription = null;
        try {
            $subscription = $user->getFirstActiveSubscription();
        } catch (\Exception $e) {
            // Catch subscription not found exception
        }

        $additional = [];

        if (! is_null($subscription)) {
            $additional['subscription'] = $subscription;
        }

        if (! is_null($user->defaultCard)) {
            $additional['card'] = $user->defaultCard;
        }

        return response()->json([
            'data' => $plans,
            'meta' => $additional,
        ]);
    }
}
