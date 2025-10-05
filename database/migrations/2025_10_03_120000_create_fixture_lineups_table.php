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
        Schema::create('fixture_lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained('fixtures')->cascadeOnDelete();
            $table->unsignedBigInteger('team_id');
            $table->string('team_name');
            $table->string('formation')->nullable();
            $table->json('starting_xi');
            $table->json('substitutes');
            $table->json('coach')->nullable();
            $table->json('raw_data');
            $table->timestamps();

            $table->unique(['fixture_id', 'team_id']);
            $table->index(['fixture_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_lineups');
    }
};
