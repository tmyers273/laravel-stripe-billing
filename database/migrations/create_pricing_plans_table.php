<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePricingPlansTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('plan_id')->nullable();
            $table->string('name', 50);
            $table->string('interval', 50)->nullable();
            $table->string('description');
            $table->string('stripe_plan_id')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique('name');
            $table->index('name');

            $table
                ->foreign('plan_id')
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
    public function down() {
        Schema::dropIfExists('pricing_plans');
    }
}
