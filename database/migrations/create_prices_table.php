<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePricingPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name', 50)->nullable();
            $table->string('interval', 50)->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->unsignedInteger('price'); // in cents
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique('name');
            $table->index('name');

            $table
                ->foreign('product_id')
                ->references('id')
                ->on(config('stripe-billing.tables.plans'))
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pricing_plans');
    }
}
