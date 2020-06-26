<?php

namespace TMyers\StripeBilling\Tests\Helpers;


use Illuminate\Database\Eloquent\Model;
use TMyers\StripeBilling\Models\Price;
use TMyers\StripeBilling\Models\Plan;
use TMyers\StripeBilling\Models\Product;

trait ProductFactory
{
    /**
     * @param array $overrides
     * @return Product
     */
    protected function createFreePlan(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'stripe_product_id' => 'prod_HXB1GforslJ5lO',
            'description' => 'Free plan',
            'name' => 'free',
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return Product
     */
    protected function createBasicPlan(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'stripe_product_id' => 'prod_HXB1GforslJ5lO',
            'description' => 'Basic plan',
            'name' => 'basic',
        ], $overrides));
    }

    /**
     * @param array $overrides
     * @return Product
     */
    protected function createTeamPlan(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'stripe_product_id' => 'prod_HXB1GforslJ5lO',
            'description' => 'Team plan',
            'name' => 'team',
        ], $overrides));
    }

    /**
     * @param Product $plan
     * @param array $attributes
     * @return Model
     */
    protected function createPrice(Product $plan, array $attributes): Model {
        return $plan->prices()->create($attributes);
    }

    /**
     * @param array $overrides
     * @return Price
     */
    protected function createMonthlyPrice(array $overrides = []): Price {
        return Price::create(array_merge([
            'product_id' => null,
            'name' => 'monthly',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 2000,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param Product $product
     * @param array $overrides
     * @return Price
     */
    protected function createBasicMonthlyPrice(Product $product = null, array $overrides = []): Price {
        $product = $product ?: $this->createBasicPlan();

        return Price::create(array_merge([
            'product_id' => $product->id,
            'name' => 'basic-monthly',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 1500,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param Product $product
     * @param array $overrides
     * @return Price
     */
    protected function createBasicYearlyPrice(Product $product = null, array $overrides = []): Price {
        $product = $product ?: $this->createBasicPlan();

        return Price::create(array_merge([
            'product_id' => $product->id,
            'name' => 'basic-yearly-9000',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 90000,
            'active' => true,
        ], $overrides));
    }

    /**
     * @param Product $product
     * @param array $overrides
     * @return Price
     */
    protected function createTeamMonthlyPrice(Product $product = null, array $overrides = []): Price
    {
        $product = $product ?: $this->createTeamPlan();

        return Price::create(array_merge([
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
     * @return Price
     */
    public function createInactivePrice(array $overrides = []): Price
    {
        return Price::create(array_merge([
            'name' => 'team-monthly-24',
            'interval' => 'month',
            'stripe_price_id' => 'price_1Gy6fZDPgfzJHRbDGaGQlMkN',
            'price' => 5500,
            'active' => false,
        ], $overrides));
    }
}
