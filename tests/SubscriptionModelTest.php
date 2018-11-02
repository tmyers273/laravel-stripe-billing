<?php

namespace TMyers\StripeBilling\Tests;


use Carbon\Carbon;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\PlanIsInactive;
use TMyers\StripeBilling\Exceptions\StripeBillingException;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\PricingPlan;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Helpers\SubscriptionFactory;
use TMyers\StripeBilling\Tests\Stubs\Models\User;
use Mockery as m;

class SubscriptionModelTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $user = $this->createUser();
        $plan = $this->createBasicMonthlyPricingPlan();

        $subscription = Subscription::create([
            'owner_id' => $user->id,
            'pricing_plan_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
        ]);

        $this->assertInstanceOf(User::class, $subscription->owner);
        $this->assertInstanceOf(PricingPlan::class, $subscription->pricingPlan);
        $this->assertInstanceOf(Plan::class, $subscription->pricingPlan->plan);
    }
    
    /*
    |--------------------------------------------------------------------------
    | Periods and active status
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_verify_on_trial_period()
    {
        $user = $this->createUser();
        $plan = $this->createMonthlyPricingPlan();

        // Given we have active on trial subscription
        $onTrialSubscription = $this->createOnTrialSubscription($user, $plan);

        $this->assertTrue($onTrialSubscription->onTrial());
        $this->assertTrue($onTrialSubscription->isActive());

        $this->assertFalse($onTrialSubscription->onGracePeriod());

        // Given we have on trial subscription where on trial already ended
        $expiredOnTrialSubscription = $this->createOnTrialSubscription($user, $plan, ['trial_ends_at' => now()->subDays(5)]);

        // Expect subscription not to be on trial any more still but active
        $this->assertFalse($expiredOnTrialSubscription->onTrial());
        $this->assertTrue($expiredOnTrialSubscription->isActive());

        $this->assertFalse($expiredOnTrialSubscription->onGracePeriod());
    }

    /** @test */
    public function it_can_verify_on_grace_period()
    {
        $user = $this->createUser();
        $plan = $this->createMonthlyPricingPlan();

        // Given we have subscription with is under the grace period
        $graceSubscription = $this->createGraceSubscription($user, $plan);

        // expect subscription to be on grace period and still active
        $this->assertTrue($graceSubscription->onGracePeriod());
        $this->assertTrue($graceSubscription->isActive());

        $this->assertFalse($graceSubscription->onTrial());
    }

    /** @test */
    public function it_can_verify_on_grace_period_is_over()
    {
        $user = $this->createUser();
        $plan = $this->createMonthlyPricingPlan();

        // Given we have subscription with is over the grace period
        $graceSubscription = $this->createGraceSubscription($user, $plan, ['ends_at' => now()->subDays(4)]);

        // expect subscription to not be on grace period and not active anymore
        $this->assertFalse($graceSubscription->onGracePeriod());
        $this->assertFalse($graceSubscription->isActive());

        $this->assertFalse($graceSubscription->onTrial());
    }
    
    /*
    |--------------------------------------------------------------------------
    | Cancellation
    |--------------------------------------------------------------------------
    */
    
    /** @test */
    public function active_subscription_can_be_cancelled_at_period_end()
    {
        $user = $this->createUser();
        $plan = $this->createMonthlyPricingPlan();
        $stripeId = 'fake-id';

        // Given we have active subscription
        $activeSubscription = $this->createActiveSubscription($user, $plan, ['stripe_subscription_id' => $stripeId]);

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[save]')->makePartial();
        $stripeSubscription->shouldReceive('save')->once();

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        // we take grace period ends_at value from Stripe period end
        StripeSubscription::shouldReceive('parseCurrentPeriodEnd')
            ->once()
            ->with($stripeSubscription)
            ->andReturn(now()->addDays(1)->endOfDay());

        // Do cancel subscription
        $activeSubscription->cancelAtPeriodEnd();

        tap($activeSubscription->fresh(), function(Subscription $subscription) {
            $this->assertTrue($subscription->onGracePeriod());
            $this->assertTrue($subscription->isActive());
            $this->assertEquals(1, $subscription->daysUntilTheEndOfTheGracePeriod());
        });
    }

    /** @test */
    public function active_on_trial_subscription_can_be_cancelled_at_period_end()
    {
        $user = $this->createUser();
        $plan = $this->createMonthlyPricingPlan();
        $stripeId = 'fake-id';
        $trialEndsAt = 5;

        // Given we have on trial subscription
        $activeSubscription = $this->createOnTrialSubscription($user, $plan, [
            'stripe_subscription_id' => $stripeId,
            'trial_ends_at' => now()->addDays($trialEndsAt)->endOfDay(),
        ]);

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[save]')->makePartial();
        $stripeSubscription->shouldReceive('save')->once();

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        StripeSubscription::shouldReceive('parseCurrentPeriodEnd')->never();

        // Do cancel subscription
        $activeSubscription->cancelAtPeriodEnd();

        tap($activeSubscription->fresh(), function(Subscription $subscription) use ($trialEndsAt) {
            // Expect subscription to be active for until the end of initial trial period
            $this->assertTrue($subscription->onGracePeriod());
            $this->assertTrue($subscription->isActive());
            $this->assertEquals($trialEndsAt, $subscription->daysUntilTheEndOfTheGracePeriod());
        });
    }
    
    /** @test */
    public function subscription_can_be_cancelled_immediately()
    {
        $user = $this->createUser();
        $plan = $this->createMonthlyPricingPlan();
        $stripeId = 'fake-id';

        // Given we have active subscription
        $activeSubscription = $this->createActiveSubscription($user, $plan, [
            'stripe_subscription_id' => $stripeId,
        ]);

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[cancel]')->makePartial();
        $stripeSubscription->shouldReceive('cancel')->once();

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        // Do cancel now
        $activeSubscription->cancelNow();

        tap($activeSubscription->fresh(), function(Subscription $subscription) {
            // Expect subscription to be active for until the end of initial trial period
            $this->assertFalse($subscription->onGracePeriod());
            $this->assertFalse($subscription->isActive());
            $this->assertFalse($subscription->onTrial());
        });
    }

    /** @test */
    public function it_can_swap_plans()
    {
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();
        $basicPlan = $this->createBasicMonthlyPricingPlan($basicType = $this->createBasicPlan());
        $stripeId = 'fake-id';

        // Given we have active subscription
        $activeSubscription = $this->createActiveSubscription($user, $monthlyPlan, [
            'stripe_subscription_id' => $stripeId,
        ]);

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[save]')->makePartial();
        $stripeSubscription->shouldReceive('save')->once();

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        // Do change plan to basic monthly plan
        $activeSubscription->changeTo($basicPlan);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $activeSubscription->id,
            'type' => 'basic',
            'pricing_plan_id' => $basicPlan->id,
        ]);

        tap($activeSubscription->fresh(), function(Subscription $subscription) use ($basicPlan, $basicType) {
            // Expect subscription to have changed plan
            $this->assertTrue($subscription->isFor($basicPlan));
            $this->assertTrue($subscription->isFor($basicType));
            $this->assertTrue($subscription->isFor('basic'));

            // Expect subscription to be active for until the end of initial trial period
            $this->assertFalse($subscription->onGracePeriod());
            $this->assertTrue($subscription->isActive());
            $this->assertFalse($subscription->onTrial());
        });
    }

    /** @test */
    public function it_can_swap_plans_being_on_trial()
    {
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();
        $basicPlan = $this->createBasicMonthlyPricingPlan($basicType = $this->createBasicPlan());
        $stripeId = 'fake-id';
        $trialEndsAt = 5;

        // Given we have active on trial subscription
        $activeSubscription = $this->createOnTrialSubscription($user, $monthlyPlan, [
            'stripe_subscription_id' => $stripeId,
            'trial_ends_at' => now()->addDays($trialEndsAt)->endOfDay(),
        ]);

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[save]')->makePartial();
        $stripeSubscription->shouldReceive('save')->once();

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        // Do change plan
        $activeSubscription->changeTo($basicPlan);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $activeSubscription->id,
            'type' => 'basic',
            'pricing_plan_id' => $basicPlan->id,
        ]);

        tap($activeSubscription->fresh(), function(Subscription $subscription) use ($basicPlan, $basicType) {
            // Expect subscription to have changed plan
            $this->assertTrue($subscription->isFor($basicPlan));
            $this->assertTrue($subscription->isFor($basicType));
            $this->assertTrue($subscription->isFor('basic'));

            // Expect subscription to be active for until the end of initial trial period
            $this->assertFalse($subscription->onGracePeriod());
            $this->assertTrue($subscription->isActive());
            $this->assertTrue($subscription->onTrial());
        });
    }

    /** @test */
    public function it_will_throw_if_trying_to_change_to_plan_that_it_is_already_for()
    {
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();
        $stripeId = 'fake-id';

        // Given we have active subscription
        $activeSubscription = $this->createActiveSubscription($user, $monthlyPlan, [
            'stripe_subscription_id' => $stripeId,
        ]);

        $this->expectException(AlreadySubscribed::class);

        // Do change again to the same plan
        $activeSubscription->changeTo($monthlyPlan);
    }
    
    /** @test */
    public function it_will_throw_if_try_to_change_to_plan_that_is_not_active_any_more()
    {
        $user = $this->createUser();
        $monthlyPlan = $this->createMonthlyPricingPlan();
        $inactivePlan = $this->createInactivePricingPlan();
        $stripeId = 'fake-id';

        // Given we have active subscription
        $activeSubscription = $this->createActiveSubscription($user, $monthlyPlan, [
            'stripe_subscription_id' => $stripeId,
        ]);

        $this->expectException(PlanIsInactive::class);

        // Do change again to the same plan
        $activeSubscription->changeTo($inactivePlan);
    }

    /*
    |--------------------------------------------------------------------------
    | Resume
    |--------------------------------------------------------------------------
    */

    public function subscription_can_be_resumed_during_grace_period()
    {
        $user = $this->createUser();
        $stripeId = 'fake-id';
        $graceSubscription = $this->createGraceSubscription($user, $this->createMonthlyPricingPlan());

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[save]')->makePartial();
        $stripeSubscription->shouldReceive('save')->once();

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        $graceSubscription->resume();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $graceSubscription->id,
            'ends_at' => null,
        ]);

        tap($graceSubscription->fresh(), function(Subscription $subscription) {
            // Expect subscription to be active for until the end of initial trial period
            $this->assertFalse($subscription->onGracePeriod());
            $this->assertTrue($subscription->isActive());
            $this->assertFalse($subscription->onTrial());
        });
    }

    /** @test */
    public function it_will_throw_if_subscription_is_not_on_grace_period()
    {
        $user = $this->createUser();
        $graceSubscription = $this->createExpiredSubscription($user, $this->createMonthlyPricingPlan());

        $this->expectException(StripeBillingException::class);

        $graceSubscription->resume();
    }

    /** @test */
    public function it_will_throw_if_plan_has_become_inactive()
    {
        $user = $this->createUser();
        $graceSubscription = $this->createGraceSubscription($user, $this->createInactivePricingPlan());

        $this->expectException(PlanIsInactive::class);

        $graceSubscription->resume();
    }
}