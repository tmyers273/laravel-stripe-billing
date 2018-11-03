<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('stripe-billing.tables.cards'), function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->string('stripe_card_id');
            $table->string('brand', 30);
            $table->string('last_4', 8);
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table
                ->foreign('owner_id')
                ->references('id')
                ->on(config('stripe-billing.tables.owner'))
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('stripe-billing.tables.cards'));
    }
}
