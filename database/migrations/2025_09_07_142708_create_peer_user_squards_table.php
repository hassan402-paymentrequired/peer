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
        Schema::create('peer_user_squards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peer_user_id')->constrained('peer_users')->cascadeOnDelete();
            $table->tinyInteger('star_rating');
            $table->foreignId('main_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('sub_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('main_player_match_id')->nullable()->constrained('player_matches')->cascadeOnDelete();
            $table->foreignId('sub_player_match_id')->nullable()->constrained('player_matches')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peer_user_squards');
    }
};
