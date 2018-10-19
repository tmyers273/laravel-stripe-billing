<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('plan_type_id')->nullable();
            $table->string('name');
            $table->string('code_name', 50);
            $table->string('interval', 50);
            $table->text('description')->nullable();
            $table->string('stripe_plan_id')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique('code_name');
            $table->index('code_name');

            $table
                ->foreign('plan_type_id')
                ->references('id')
                ->on(config('stripe-billing.tables.plan_types'))
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
        Schema::dropIfExists('plans');
    }
}