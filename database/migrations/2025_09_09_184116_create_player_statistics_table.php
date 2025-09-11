<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedBigInteger('fixture_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();


            $table->date('match_date');
            $table->integer('assists')->default(0);
            $table->integer('yellow_cards')->default(0);
            $table->integer('shots_on_target')->default(0);
            $table->boolean('did_play')->default(true);
            $table->boolean('is_injured')->default(false);

            $table->integer('minutes')->nullable();
            $table->string('rating')->nullable();
            $table->boolean('captain')->nullable();
            $table->boolean('substitute')->nullable();
            $table->integer('shots_total')->nullable();
            $table->integer('goals_total')->nullable();
            $table->integer('offsides')->nullable();
            $table->integer('goals_conceded')->nullable();
            $table->integer('goals_assists')->nullable();
            $table->integer('goals_saves')->nullable();
            $table->integer('passes_total')->nullable();
            $table->string('position')->nullable();
            $table->integer('tackles_total')->nullable();
            $table->integer('number')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_statistics');
    }
};
