<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, handle any existing users - give them temporary phone numbers if they don't have one
        DB::table('users')
            ->whereNull('phone')
            ->orWhere('phone', '')
            ->update(['phone' => DB::raw("CONCAT('temp_', id)")]);

        // Check if the email unique index exists before trying to drop it
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $indexExists = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = 'users' AND index_name = 'users_email_unique'",
            [$database]
        );

        Schema::table('users', function (Blueprint $table) use ($indexExists) {
            // Make email nullable and remove unique constraint if it exists
            if ($indexExists[0]->count > 0) {
                $table->dropUnique(['email']);
            }
            $table->string('email')->nullable()->change();

            // Make phone required (NOT NULL) and unique for authentication
            $table->string('phone')->nullable(false)->unique()->change();
        });

        // Update password reset tokens to use phone instead of email
        Schema::dropIfExists('password_reset_tokens');
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('phone')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert email to required and unique
            $table->string('email')->nullable(false)->unique()->change();

            // Make phone nullable again and drop unique constraint
            $table->string('phone')->nullable()->change();
            $table->dropUnique(['phone']);
        });

        // Revert password reset tokens to use email
        Schema::dropIfExists('password_reset_tokens');
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
