<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Updates;

use DB;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * Class CreateBeveragesTable
 *
 * @package Adrenth\CoffeeManager\Updates
 */
class CreateBeveragesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('adrenth_coffeemanager_beverage_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('adrenth_coffeemanager_beverages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('group_id');
            $table->string('name');
            $table->unsignedInteger('round_count')->default(0);
            $table->timestamps();

            $table->foreign('group_id', 'beverage_group')
                 ->references('id')
                 ->on('adrenth_coffeemanager_beverage_groups')
                 ->onUpdate('cascade')
                 ->onDelete('cascade');
        });

        Schema::create('adrenth_coffeemanager_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('current_round_id')->nullable();
            $table->timestamps();
        });

        Schema::create('adrenth_coffeemanager_participants', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('group_id');
            $table->string('name');
            $table->unsignedInteger('round_count')->default(0);
            $table->unsignedInteger('default_beverage_id')->nullable();
            $table->unsignedInteger('last_beverage_id')->nullable();
            $table->timestamps();

            $table->foreign('group_id', 'participant_group')
                ->references('id')
                ->on('adrenth_coffeemanager_groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::create('adrenth_coffeemanager_rounds', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('initiating_participant_id');
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->foreign('initiating_participant_id', 'round_initiating_participant')
                ->references('id')
                ->on('adrenth_coffeemanager_participants')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('group_id', 'round_group')
                ->references('id')
                ->on('adrenth_coffeemanager_groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::create('adrenth_coffeemanager_round_participant', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('round_id');
            $table->unsignedInteger('participant_id');
            $table->unsignedInteger('beverage_id');

            $table->foreign('round_id', 'round_participant_round')
                ->references('id')
                ->on('adrenth_coffeemanager_rounds')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('participant_id', 'round_participant_participant')
                ->references('id')
                ->on('adrenth_coffeemanager_participants')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('beverage_id', 'round_participant_beverage')
                ->references('id')
                ->on('adrenth_coffeemanager_beverages')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        Schema::dropIfExists('adrenth_coffeemanager_beverages');
        Schema::dropIfExists('adrenth_coffeemanager_beverage_groups');
        Schema::dropIfExists('adrenth_coffeemanager_groups');
        Schema::dropIfExists('adrenth_coffeemanager_participants');
        Schema::dropIfExists('adrenth_coffeemanager_rounds');
        Schema::dropIfExists('adrenth_coffeemanager_round_participant');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
