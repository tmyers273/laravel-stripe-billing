<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\Models\StripePrice;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\StripeProduct;

trait ProductFactory
{
    /**
     * @param array $overrides
     * @return StripeProduct
     */
    protected function createFreePlan(array $overrides = []): StripeProduct
    {
        return StripeProduct::create(array_merge([
            'stripe_product_id' => 'prod_HXB1GforslJ5lO',
            'description' => 'Free plan',
            'name' => 'free',
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return StripeProduct
     */
    protected function createBasicPlan(array $overrides = []): StripeProduct
    {
        return StripeProduct::create(array_merge([
            'stripe_product_id' => 'prod_HXB1GforslJ5lO',
            'description' => 'Basic plan',
            'name' => 'basic',
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return StripeProduct
     */
    protected function createTeamPlan(array $overrides = []): StripeProduct
    {
        return StripeProduct::create(array_merge([
            'stripe_product_id' => 'prod_HXB1GforslJ5lO',
            'description' => 'Team plan',
            'name' => 'team',
        ], $overrides));
    }

    /**
     * @param StripeProduct $plan
     * @param array $attributes
     * @return Model
     */
    protected function createPrice(StripeProduct $plan, array $attributes): Model {
        return $plan->prices()->create($attributes);
    }

    /**
     * @param array $overrides
     * @return StripePrice
     */
    protected function createMonthlyPrice(array $overrides = []): StripePrice {
        return StripePrice::create(array_merge([
            'product_id' => null,
            'name' => 'monthly',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 2000,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param StripeProduct $product
     * @param array $overrides
     * @return StripePrice
     */
    protected function createBasicMonthlyPrice(StripeProduct $product = null, array $overrides = []): StripePrice {
        $product = $product ?: $this->createBasicPlan();

        return StripePrice::create(array_merge([
            'product_id' => $product->id,
            'name' => 'basic-monthly',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 1500,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param StripeProduct $product
     * @param array $overrides
     * @return StripePrice
     */
    protected function createBasicYearlyPrice(StripeProduct $product = null, array $overrides = []): StripePrice {
        $product = $product ?: $this->createBasicPlan();

        return StripePrice::create(array_merge([
            'product_id' => $product->id,
            'name' => 'basic-yearly-9000',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 90000,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param StripeProduct $product
     * @param array $overrides
     * @return StripePrice
     */
    protected function createTeamMonthlyPrice(StripeProduct $product = null, array $overrides = []): StripePrice
    {
        $product = $product ?: $this->createTeamPlan();

        return StripePrice::create(array_merge([
            'product_id' => $product->id,
            'name' => 'team-monthly-10',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 4500,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return StripePrice
     */
    public function createInactivePrice(array $overrides = []): StripePrice
    {
        return StripePrice::create(array_merge([
            'name' => 'team-monthly-24',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 5500,
            'active' => false,
        ], $overrides));
    }
}
