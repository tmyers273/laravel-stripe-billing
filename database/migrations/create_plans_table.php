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
            $table->string('name', 50);
            $table->string('description');
            $table->text('detailed_description')->nullable();
            $table->boolean('is_free');
            $table->boolean('active')->default(true);
            $table->boolean('teams_enabled')->default(false);
            $table->unsignedInteger('team_users_limit')->nullable();
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
        Schema::dropIfExists('plans');
    }
}