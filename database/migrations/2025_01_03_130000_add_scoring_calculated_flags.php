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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->boolean('scoring_calculated')->default(false)->after('status');
            $table->timestamp('scoring_calculated_at')->nullable()->after('scoring_calculated');
        });

        Schema::table('peers', function (Blueprint $table) {
            $table->boolean('scoring_calculated')->default(false)->after('status');
            $table->timestamp('scoring_calculated_at')->nullable()->after('scoring_calculated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['scoring_calculated', 'scoring_calculated_at']);
        });

        Schema::table('peers', function (Blueprint $table) {
            $table->dropColumn(['scoring_calculated', 'scoring_calculated_at']);
        });
    }
};
