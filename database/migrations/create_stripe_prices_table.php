<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStripePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_prices', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name', 50)->nullable();
            $table->string('interval', 50)->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->unsignedInteger('price'); // in cents
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique('name');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stripe_prices');
    }
}
