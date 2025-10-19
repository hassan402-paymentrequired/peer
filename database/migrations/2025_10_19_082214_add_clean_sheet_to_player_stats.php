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
        Schema::table('player_statistics', function (Blueprint $table) {
            $table->integer('clean_sheet')->nullable();
            $table->integer('shots_on_goal')->default(0);
            $table->integer('red_cards')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_statistics', function (Blueprint $table) {
            $table->dropColumn('clean_sheet');
            $table->dropColumn('shots_on_goal');
            $table->dropColumn('red_cards');
        });
    }
};
