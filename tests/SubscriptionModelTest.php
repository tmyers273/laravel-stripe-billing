<?php

namespace TMyers\StripeBilling\Tests;

use Carbon\Carbon;
use TMyers\StripeBilling\Exceptions\AlreadySubscribed;
use TMyers\StripeBilling\Exceptions\PriceIsInactive;
use TMyers\StripeBilling\Exceptions\StripeBillingException;
use TMyers\StripeBilling\Facades\StripeSubscription;
use TMyers\StripeBilling\Models\StripeProduct;
use TMyers\StripeBilling\Models\StripePrice;
use TMyers\StripeBilling\Models\Subscription;
use TMyers\StripeBilling\Tests\Stubs\Models\User;
use Mockery as m;

class SubscriptionModelTest extends TestCase
{
    protected function tearDown(): void {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $user = $this->createUser();
        $plan = $this->createBasicMonthlyPrice();

        $subscription = Subscription::create([
            'owner_id' => $user->id,
            'price_id' => $plan->id,
            'stripe_subscription_id' => 'fake-stripe-id',
        ]);

        $this->assertInstanceOf(User::class, $subscription->owner);
        $this->assertInstanceOf(StripePrice::class, $subscription->price);
        $this->assertInstanceOf(StripeProduct::class, $subscription->price->product);
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
        $plan = $this->createMonthlyPrice();

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
        $plan = $this->createMonthlyPrice();

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
        $plan = $this->createMonthlyPrice();

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
        $plan = $this->createMonthlyPrice();
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
        $plan = $this->createMonthlyPrice();
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
        $plan = $this->createMonthlyPrice();
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
        $monthlyPlan = $this->createMonthlyPrice();
        $basicPlan = $this->createBasicMonthlyPrice($basicType = $this->createBasicPlan());
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
            'price_id' => $basicPlan->id,
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
        $monthlyPlan = $this->createMonthlyPrice();
        $basicPlan = $this->createBasicMonthlyPrice($basicType = $this->createBasicPlan());
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
            'price_id' => $basicPlan->id,
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
        $monthlyPlan = $this->createMonthlyPrice();
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
        $monthlyPlan = $this->createMonthlyPrice();
        $inactivePlan = $this->createInactivePrice();
        $stripeId = 'fake-id';

        // Given we have active subscription
        $activeSubscription = $this->createActiveSubscription($user, $monthlyPlan, [
            'stripe_subscription_id' => $stripeId,
        ]);

        $this->expectException(PriceIsInactive::class);

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
        $graceSubscription = $this->createGraceSubscription($user, $this->createMonthlyPrice());

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
        $graceSubscription = $this->createExpiredSubscription($user, $this->createMonthlyPrice());

        $this->expectException(StripeBillingException::class);

        $graceSubscription->resume();
    }

    /** @test */
    public function it_will_throw_if_plan_has_become_inactive()
    {
        $user = $this->createUser();
        $graceSubscription = $this->createGraceSubscription($user, $this->createInactivePrice());

        $this->expectException(PriceIsInactive::class);

        $graceSubscription->resume();
    }

    /** @test */
    public function it_can_retrieve_all_canceled_and_archived_subscriptions()
    {
        $canceledA = $this->createExpiredSubscription($this->createUser(), $this->createMonthlyPrice());
        $canceledB = $this->createExpiredSubscription($this->createUser(), $this->createBasicYearlyPrice());
        $graceA = $this->createGraceSubscription($this->createUser(), $this->createInactivePrice());
        $active = $this->createActiveSubscription($this->createUser(), $this->createTeamMonthlyPrice());
        $trial = $this->createOnTrialSubscription($this->createUser(), $this->createMonthlyPrice(['name' => 'monthly_10']));

        $archived = Subscription::canceledAndArchived()->get();

        $this->assertCount(2, $archived);
        $this->assertTrue($archived[0]->is($canceledA));
        $this->assertTrue($archived[1]->is($canceledB));
    }

    /*
    |--------------------------------------------------------------------------
    | Trial modification
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function trial_can_be_changed_by_timestamp() {
        // 1. Given
        $user = $this->createUser();
        $subscription = $this->createOnTrialSubscription($user, $this->createBasicMonthlyPrice());
        $timestamp = Carbon::now()->addDays(35)->getTimestamp();

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[save]')->makePartial();
        $stripeSubscription->shouldReceive('save')->once();
        $stripeId = 'fake-stripe-id';

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        // 2. Do this
        $subscription->trialEndAt($timestamp);

        // 3.1 Expect
        $this->assertEquals(Carbon::createFromTimestamp($timestamp), $subscription->trial_ends_at);
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->onTrial());
    }

    /** @test */
    public function trial_can_be_changed_by_adding_days() {
        // 1. Given
        $user = $this->createUser();
        $subscription = $this->createOnTrialSubscription($user, $this->createBasicMonthlyPrice());
        $timestamp = Carbon::now()->addDays(35)->getTimestamp();

        // Mock
        $stripeSubscription = m::mock('Stripe\Subscription[save]')->makePartial();
        $stripeSubscription->shouldReceive('save')->once();
        $stripeId = 'fake-stripe-id';

        StripeSubscription::shouldReceive('retrieve')
            ->once()
            ->with($stripeId)
            ->andReturn($stripeSubscription);

        // 2. Do this
        $subscription->addDaysToTrial(10);

        // 3.1 Expect
        $this->assertEquals(Carbon::createFromTimestamp($timestamp), $subscription->trial_ends_at);
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->onTrial());
    }
}
