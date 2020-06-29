<?php

namespace TMyers\StripeBilling\Commands;

use Illuminate\Console\Command;
use Stripe\Product as StripeProduct;
use Stripe\Price as StripePrice;
use TMyers\StripeBilling\Gateways\StripeGateway;
use TMyers\StripeBilling\Models\StripeProduct as LocalProduct;
use TMyers\StripeBilling\Models\StripePrice as LocalPrice;

class BillingSyncProducts extends Command
{
    protected $client;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:sync-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs Stripe Products and Prices';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->client = StripeGateway::client();

        $this->updateProducts();
    }

    protected function updateProducts() {
        $this->warn('All prices created will default to a 30 day trial period!');
        $this->warn('');

        $this->info("[Fetching Products] Querying Stripe");
        $products = $this->client->products->all();
        $this->info("[Fetching Products] Done. Found " . count($products));

        foreach($products as $product) {
            $local = LocalProduct::whereStripeProductId($product->id)->first();

            $updatePrices = true;
            if ($local) {
                $this->askUpdateProduct($product, $local);
            } else {
                $updatePrices = $this->askCreateProduct($product);
            }

            if ($updatePrices) {
                $this->updatePrices($product);
            }
        }
    }

    protected function askUpdateProduct(StripeProduct $product, LocalProduct $local) {
        $update = $this->pullProductData($product);
        $current = $local->only(array_keys($update));
        $diff = array_diff($update, $current);

        if (count($diff) == 0) {
            $this->info("[$product->id] Nothing to update");
            return;
        }

        $change = [];
        foreach($diff as $key => $value) {
            $change[$key] = [
                'from' => $current[$key],
                'to' => $update[$key],
            ];
        }

        $this->info("[$product->id] Trying to update Product " . print_r($change, true));
        if ($this->confirm('Do you want to make this change?')) {
            $local->update($diff);
        }
    }

    protected function askCreateProduct(StripeProduct $product): bool {
        $create = $this->pullProductData($product);

        $this->info("[$product->id] Trying to create Product " . print_r($create, true));
        if ($this->confirm('Do you want to create this product?')) {
            LocalProduct::create($create);
            return true;
        }

        return false;
    }

    protected function pullProductData(StripeProduct $product): array {
        return [
            'name' => $product->name,
            'description' => $product->description,
            'active' => $product->active,
            'stripe_product_id' => $product->id,
        ];
    }

    protected function updatePrices(StripeProduct $product) {
        $this->info("[$product->id] Querying Stripe for Prices");
        $prices = $this->client->prices->all([
            'product' => $product->id,
        ]);

        // Sort by prices, in ascending order
        $prices = collect($prices->data)->sortBy('unit_amount');
        $this->info("[$product->id] Done. Found " . count($prices));

        $localProduct = LocalProduct::whereStripeProductId($product->id)->first();
        foreach($prices as $price) {
            $local = LocalPrice::whereStripePriceId($price->id)->first();

            if ($local) {
                $this->askUpdatePrice($price, $local, $product, $localProduct);
            } else {
                $this->askCreatePrice($price, $product, $localProduct);
            }
        }
    }

    protected function askUpdatePrice(StripePrice $price, LocalPrice $local, StripeProduct $product, LocalProduct $localProduct) {
        $update = $this->pullPriceData($price, $localProduct);
        $current = $local->only(array_keys($update));
        $diff = array_diff($update, $current);

        if (count($diff) == 0) {
            $this->info("[$product->id] [$price->id] Nothing to update");
            return;
        }

        $change = [];
        foreach($diff as $key => $value) {
            $change[$key] = [
                'from' => $current[$key],
                'to' => $update[$key],
            ];
        }

        $this->info("[$product->id] [$price->id] Trying to update Price " . print_r($change, true));
        if ($this->confirm('Do you want to make this change?')) {
            $local->update($diff);
        }
    }

    protected function askCreatePrice(StripePrice $price, StripeProduct $product, LocalProduct $localProduct): bool {
        $create = $this->pullPriceData($price, $localProduct);

        $this->info("[$product->id] [$price->id] Trying to create Price " . print_r($create, true));
        if ($this->confirm('Do you want to create this?')) {
            LocalPrice::create($create);
            return true;
        }

        return false;
    }

    protected function pullPriceData(StripePrice $price, LocalProduct $localProduct): array {
        return [
            'product_id' => $localProduct->id,
            'stripe_price_id' => $price->id,
            'name' => $price->nickname,
            'interval' => $price->recurring->interval,
            'price' => $price->unit_amount,
            'active' => $price->active,
        ];
    }
}
