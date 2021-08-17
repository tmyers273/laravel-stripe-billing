<?php

namespace TMyers\StripeBilling\Tests;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;

class BladeDirectivesTest extends TestCase {
    public function setUp() {
        parent::setUp();

        // Views for testing
        $finder = new \Illuminate\View\FileViewFinder(app()['files'], array(__DIR__ . '/views'));
        View::setFinder($finder);
    }

    /** @test */
    public function all_blade_directives_will_evaluate_to_false_for_guest_user() {
        $this->assertEquals("Hi, I'm absolutely not subscribed", $this->renderView('subscribed'));
        $this->assertEquals("Hi, I'm either not logged in or subscribed", $this->renderView('unsubscribed'));
    }

    /** @test */
    public function subscribed_directive_will_evaluate_to_true_if_logged_user_is_subscribed() {
        $user = $this->createUser();
        $subscription = $this->createActiveSubscription($user, $this->createMonthlyPricingPlan());

        // Do login
        $this->be($user);

        // Expect user to be subscribed
        $this->assertEquals("Hi, I'm subscribed", $this->renderView('subscribed'));
        $this->assertEquals("Hi, I'm either not logged in or subscribed", $this->renderView('unsubscribed'));
    }

    /** @test */
    public function unless_subscribed_directive_will_evaluate_to_true_if_logged_user_is_not_subscribed() {
        $user = $this->createUser();

        // Do login
        $this->be($user);

        // Expect user to be subscribed
        $this->assertEquals("Hi, I'm absolutely not subscribed", $this->renderView('subscribed'));
        $this->assertEquals("Hi, I'm logged in but not subscribed", $this->renderView('unsubscribed'));
    }

    protected function renderView(string $view, array $parameters = []) {
        Artisan::call('view:clear');

        $view = view($view)->with($parameters);

        return trim((string)$view);
    }
}
