<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 18.10.2018
 * Time: 23:48
 */

namespace TMyers\StripeBilling\Tests\Integration;


use TMyers\StripeBilling\Facades\StripeCustomer;
use TMyers\StripeBilling\Tests\TestCase;

class StripeCustomerTest extends TestCase
{
    public function setUp()
    {
        if (!env('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Integration tests are being skipped. See phpunit.xml');
        }

        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_customer()
    {
        $customer = StripeCustomer::create($this->createTestToken(), 'tester@test.com');

        $this->assertNotNull($customer->id);
    }
}