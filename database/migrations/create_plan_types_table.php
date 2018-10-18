<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlanTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code_name', 50);
            $table->text('description')->nullable();
            $table->boolean('is_free');
            $table->boolean('active')->default(true);
            $table->boolean('teams_enabled')->default(false);
            $table->unsignedInteger('team_users_limit')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique('code_name');
            $table->index('code_name');
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