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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_ref', 50)->unique();
            $table->enum('action_type', ['debit', 'credit']);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->decimal('wallet_balance_before', 20, 2)->default(0);
            $table->decimal('wallet_balance_after', 20, 2)->default(0);
            $table->integer('status')->comment('1 - pending | 2 - successful | 3 - failed');
            $table->text('meta_data')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamp('last_checked')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
