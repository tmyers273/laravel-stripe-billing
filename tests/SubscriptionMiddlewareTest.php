<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 21.10.2018
 * Time: 21:17
 */

namespace TMyers\StripeBilling\Tests;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use TMyers\StripeBilling\Middleware\SubscriptionMiddleware;

class SubscriptionMiddlewareTest extends TestCase
{
    /** @test */
    public function user_with_active_subscriptions_passes_validation()
    {
        // Given we have a user, am active plan and an active subscription
        $user = $this->createUser();
        $monthlyPricingPlan = $this->createMonthlyPricingPlan();

        $this->createActiveSubscription($user, $monthlyPricingPlan);

        // Initialize fake request
        $this->be($user);

        $request = Request::create('/only/for/subscribed/users', 'GET');

        $request->setUserResolver(function() {
            return auth()->user();
        });

        $middleware = new SubscriptionMiddleware();

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        });

        // Assert middleware to call the $next Closure
        $this->assertSame($request, $response);
    }

    /** @test */
    public function user_without_active_subscription_will_not_pass_the_middleware()
    {
        // Given we have a user, am active plan and an active subscription
        $user = $this->createUser();
        $monthlyPricingPlan = $this->createMonthlyPricingPlan();

        $this->createExpiredSubscription($user, $monthlyPricingPlan);

        // Initialize fake request
        $this->be($user);

        $request = Request::create('/only/for/subscribed/users', 'GET');

        $request->setUserResolver(function() {
            return auth()->user();
        });

        // Expect unauthorized exception
        $this->expectException(HttpExceptionInterface::class);
        $this->expectExceptionMessage('Unauthorized.');

        $middleware = new SubscriptionMiddleware();

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        });
    }

    /** @test */
    public function user_without_active_subscription_will_not_pass_the_middleware_json_request()
    {
        // Given we have a user, am active plan and an active subscription
        $user = $this->createUser();
        $monthlyPricingPlan = $this->createMonthlyPricingPlan();

        $this->createExpiredSubscription($user, $monthlyPricingPlan);

        // Initialize fake request
        $this->be($user);

        $request = Request::create('/only/for/subscribed/users', 'GET');

        $request->setUserResolver(function() {
            return auth()->user();
        });

        // When client expects JSON
        $request->headers->set('Accept', 'application/json');

        $middleware = new SubscriptionMiddleware();

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        });

        // We expect response to be an instance of JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // We expect response to have a 403 code
        $this->assertEquals(403, $response->status());
    }

    /** @test */
    public function unauthenticated_user_will_not_pass_the_middleware()
    {
        // Given we have a user, am active plan and an active subscription
        $user = $this->createUser();
        $monthlyPricingPlan = $this->createMonthlyPricingPlan();

        $this->createActiveSubscription($user, $monthlyPricingPlan);

        // Initialize fake request
        $request = Request::create('/only/for/subscribed/users', 'GET');

        $request->setUserResolver(function() {
            return auth()->user();
        });

        // Expect unauthorized exception
        $this->expectException(HttpExceptionInterface::class);
        $this->expectExceptionMessage('Unauthorized.');

        $middleware = new SubscriptionMiddleware();

        // Run middleware
        $middleware->handle($request, function($r) {
            return $r;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | With parameters
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function user_with_active_subscriptions_passes_validation_with_parameters()
    {
        // Given we have a user, am active plan and an active subscription
        $user = $this->createUser();
        $monthlyPricingPlan = $this->createMonthlyPricingPlan();
        $basicMonthlyPlan = $this->createBasicMonthlyPricingPlan();

        $this->createActiveSubscription($user, $monthlyPricingPlan);
        $this->createOnTrialSubscription($user, $basicMonthlyPlan);

        // Initialize fake request
        $this->be($user);

        $request = Request::create('/only/for/subscribed/users', 'GET');

        $request->setUserResolver(function() {
            return auth()->user();
        });

        $middleware = new SubscriptionMiddleware();

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        }, 'monthly,basic');

        // Assert middleware to call the $next Closure
        $this->assertSame($request, $response);

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        }, 'monthly');

        // Assert middleware to call the $next Closure
        $this->assertSame($request, $response);

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        }, 'basic');

        // Assert middleware to call the $next Closure
        $this->assertSame($request, $response);

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        }, 'basic,monthly');

        // Assert middleware to call the $next Closure
        $this->assertSame($request, $response);

        // Run middleware
        $response = $middleware->handle($request, function($r) {
            return $r;
        }, 'basic-monthly,basic');

        // Assert middleware to call the $next Closure
        $this->assertSame($request, $response);
    }
}