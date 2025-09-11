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
        Schema::create('player_matches', function (Blueprint $table) {
            $table->id();
            $table->ulid('player_match_id');
            $table->date('date');
            $table->time('time');
            $table->boolean('is_completed')->default(false);
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('opponent_team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_matches');
    }
};
