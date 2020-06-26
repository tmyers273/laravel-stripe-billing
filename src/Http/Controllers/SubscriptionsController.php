<?php

namespace TMyers\StripeBilling\Http\Controllers;

use App\Http\Requests\UpdateSubscriptionRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stripe\Error\InvalidRequest;
use Stripe\Invoice;
use Stripe\Stripe;
use TMyers\StripeBilling\Billable;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\CardException;
use TMyers\StripeBilling\Exceptions\PlanIsInactive;
use TMyers\StripeBilling\Exceptions\StripeBillingException;
use TMyers\StripeBilling\Http\Requests\ApplyCouponRequest;
use TMyers\StripeBilling\Http\Requests\CreateSubscriptionRequest;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Services\UserSubscriptionManager;

class SubscriptionsController extends Controller
{
    /**
     * @var UserSubscriptionManager
     */
    private $usm;

    /**
     * SubscriptionsController constructor.
     * @param UserSubscriptionManager $usm
     */
    public function __construct(UserSubscriptionManager $usm)
    {
        $this->usm = $usm;
    }

    public function store(CreateSubscriptionRequest $request) {
        /** @var Billable $user */
        $user = auth()->user();

        // Try to delete any "dummy" subscriptions that are created when a user first signs up
        if (optional($user->subscriptions->first())->stripe_subscription_id === "") {
            $user->subscriptions()->delete();
        }

        // Only one subscription is allowed
        if ($user && $user->hasActiveSubscriptions()) {
            return response()->json(['error' => 'No more than one subscription is allowed.'], 403);
        }

        try {
            // Delete all cards that could have remained from previous canceled and archived subscriptions
            $this->usm->resetUserCards($user);

            $card = $this->usm->newDefaultCard($user, $request->stripeToken);
            $subscription = $this->usm->subscribe($user->fresh(), $request->getStripePrice());
        } catch (StripeBillingException $e) {
            return response()->json($e->getMessage(), 500);
        } catch (CardException $e) {
            return response()->json($e->getMessage(), 400);
        }

        return response()->json($subscription);
    }

    /**
     * @param Subscription $subscription
     * @param UpdateSubscriptionRequest $request
     * @return JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \TMyers\StripeBilling\Exceptions\PriceIsInactive
     */
    public function update(Subscription $subscription, UpdateSubscriptionRequest $request) {
        /** @var Billable $user */
        $user = auth()->user();

        if ($subscription->owner_id !== $user->id) {
            return response()->json(['error' => 'Not your subscription.'], 403);
        }

        try {
            $this->usm->updateSubscription($subscription, $request->getStripePrice());
        } catch (AlreadySubscribed $e) {
            return response()->json($e->getMessage(), 400);
        } catch (PlanIsInactive $e) {
            return response()->json($e->getMessage(), 400);
        }

        return response()->json($subscription->fresh());
    }

    /**
     * @param Subscription $subscription
     * @param Request $request
     * @return JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function resume(Subscription $subscription, Request $request) {
        /** @var Billable $user */
        $user = auth()->user();

        if ($subscription->owner_id !== $user->id) {
            return response()->json(['error' => 'Not your subscription.'], 403);
        }

        if (!$subscription->onGracePeriod()) {
            return response()->json(['error' => 'Subscription is not on grace period'], 400);
        }

        try {
            $this->usm->resumeSubscription($subscription);
        } catch (StripeBillingException $e) {
            return response()->json($e->getMessage(), 400);
        }

        return response()->json($subscription->fresh());
    }

    public function destroy(Subscription $subscription, Request $request)
    {
        try {
            $this->usm->cancelSubscription($subscription, $atPeriodEnd = true);
        } catch (\Throwable $t) {
            return response()->json($t->getMessage(), 400);
        }

        return response()->json($subscription->fresh());
    }

    public function extendTrial(Subscription $subscription, $days = 60): \Stripe\Subscription {
        // Extend Stripe trial X days
        app(\Stripe\Stripe::class)->setApiKey(env('STRIPE_SECRET'));
        $stripeSub = app(\Stripe\Subscription::class)::retrieve($subscription->stripe_subscription_id);
        $trialEnd = $stripeSub['trial_end'];

        $newEnd = Carbon::createFromTimestamp($trialEnd)->addDays($days - config('stripe-billing.trial-days'));

        \Log::info("Trying to extend trial from " . $trialEnd . " to " . $newEnd->toIso8601ZuluString());

        $newEndTimestamp = $newEnd->timestamp;
        app(\Stripe\Subscription::class)::update($subscription->stripe_subscription_id, [
            'trial_end' => $newEndTimestamp,
        ]);

        $subscription->update([
            'trial_ends_at' => $newEnd->timestamp,
        ]);

        return $stripeSub;
    }

    // @todo unit test
    public function applyCoupon(Subscription $subscription, ApplyCouponRequest $request) {
        $user = auth()->user();

        $coupon = $request->coupon;
        if (! empty($coupon)) {
            try {
                $user->applyCoupon($coupon);
            } catch (InvalidRequest $e){
                return response()->json([
                    'message' => $e->getMessage(),
                ], 400);
            }
        }

        $coupon = Coupon::create([
            'user_id' => $user->id,
            'coupon' => $request->coupon,
        ]);

        $subscription->update([
            'coupon_id' => $coupon->id,
        ]);

        return response()->json();
    }

    // @todo unit test
    public function nextInvoice(Request $request) {
        $user = auth()->user();

        $invoice = null;
        if (! empty($user->stripe_id)) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $invoice = Invoice::upcoming([
                'customer' => $user->stripe_id,
            ]);
        }

        return response()->json([
            'invoice' => $invoice
        ], 200);
    }

    public function invoices(Request $request) {
        $user = auth()->user();

        // @todo make sure this doesn't error if no subscriptions
        $subscriptionId = optional($user->subscriptions->first())->stripe_subscription_id;

        if (empty($subscriptionId)) {
            return response()->json([
                'data' => []
            ]);
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        // @todo - this bakes in a limit of only the most recent 100 invoices will be displayed
        // @todo - to fix, we must paginate this. Not relevant for 10 years or so though!
        $invoices = Invoice::all([
            'limit' => 100,
            'subscription' => $subscriptionId,
        ]);

        return response()->json($invoices);
    }
}
