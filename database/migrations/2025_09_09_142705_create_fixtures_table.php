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
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('external_id')->unique();
            $table->unsignedBigInteger('league_id');
            $table->string('season');
            $table->dateTime('date');
            $table->bigInteger('timestamp');
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_city')->nullable();
            $table->unsignedBigInteger('home_team_id');
            $table->string('home_team_name');
            $table->string('home_team_logo')->nullable();
            $table->unsignedBigInteger('away_team_id');
            $table->string('away_team_name');
            $table->string('away_team_logo')->nullable();
            $table->string('status')->nullable();
            $table->integer('goals_home')->nullable();
            $table->integer('goals_away')->nullable();
            $table->integer('score_halftime_home')->nullable();
            $table->integer('score_halftime_away')->nullable();
            $table->integer('score_fulltime_home')->nullable();
            $table->integer('score_fulltime_away')->nullable();
            $table->json('raw_json');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
