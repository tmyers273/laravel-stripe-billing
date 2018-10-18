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
            $table->string('name');
            $table->string('code', 50);
            $table->string('interval', 50);
            $table->text('description')->nullable();
            $table->string('stripe_plan_id');
            $table->unsignedInteger('price');
            $table->boolean('active')->default(false);
            $table->boolean('teams_enabled')->default(false);
            $table->unsignedInteger('team_users_limit')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique('code');
            $table->unique('stripe_plan_id');
            $table->index('code');
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