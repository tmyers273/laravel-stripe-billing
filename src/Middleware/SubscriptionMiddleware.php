<?php

namespace TMyers\StripeBilling\Middleware;

use Closure;

class SubscriptionMiddleware {
    public function handle($request, Closure $next, $plans = null) {
        $user = $request->user();

        if (! $plans && optional($user)->hasActiveSubscriptions()) {
            return $next($request);
        }

        $plans = is_string($plans) ? explode(',', $plans) : [];

        foreach ($plans as $plan) {
            if ($user->isSubscribedTo($plan)) {
                return $next($request);
            }
        }

        return $this->forbiddenResponse($request);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse($request) {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json('Unauthorized.', 403);
        }

        abort(403, 'Unauthorized.');
    }
}
