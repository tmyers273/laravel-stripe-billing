<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStripeIdToOwnerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('stripe-billing.tables.owner'), function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->collation('utf8mb4_bin');
            $table->unsignedInteger('default_card_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('stripe-billing.tables.owner'), function(Blueprint $table) {
            $table->dropColumn('stripe_id', 'default_card_id');
        });
    }
}