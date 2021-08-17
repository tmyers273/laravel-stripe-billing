<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->unsignedInteger('pricing_plan_id');
            $table->string('type', 50)->default('default');
            $table->string('stripe_subscription_id');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table
                ->foreign('owner_id')
                ->references('id')
                ->on(config('stripe-billing.tables.owner'))
                ->onDelete('cascade');

            $table
                ->foreign('pricing_plan_id')
                ->references('id')
                ->on(config('stripe-billing.tables.pricing_plans'))
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('subscriptions');
    }
}
