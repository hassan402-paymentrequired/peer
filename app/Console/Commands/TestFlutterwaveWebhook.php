<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Enum\TransactionStatusEnum;
use App\Utils\Services\Wallet\WalletService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TestFlutterwaveWebhook extends Command
{
    protected $signature = 'test:flutterwave-webhook {--user-id=1} {--amount=1000} {--status=SUCCESSFUL}';
    protected $description = 'Test Flutterwave webhook functionality end-to-end';

    public function handle()
    {
        $this->info('🧪 Testing Flutterwave Webhook End-to-End');
        $this->newLine();

        $userId = $this->option('user-id');
        $amount = $this->option('amount');
        $status = $this->option('status');

        // Step 1: Find or create test user
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ User with ID {$userId} not found");
            return 1;
        }

        $this->info("👤 Testing with user: {$user->name} (ID: {$user->id})");

        // Step 2: Create a test transaction
        $reference = 'STA_TEST_' . Str::random(12);
        $this->info("📝 Creating test transaction with reference: {$reference}");

        $transaction = Transaction::create([
            'transaction_ref' => $reference,
            'user_id' => $user->id,
            'action_type' => 'debit',
            'description' => 'Test wallet withdrawal',
            'amount' => $amount,
            'wallet_balance_before' => $user->wallet->balance ?? 0,
            'wallet_balance_after' => ($user->wallet->balance ?? 0) - $amount,
            'status' => TransactionStatusEnum::PENDING->value,
            'meta_data' => json_encode([
                'test_transaction' => true,
                'created_by_command' => true,
            ]),
        ]);

        $this->info("✅ Test transaction created (ID: {$transaction->id})");

        // Step 3: Simulate webhook payload
        $webhookPayload = [
            'event' => 'transfer.completed',
            'data' => [
                'id' => 'flw_test_' . time(),
                'reference' => $reference,
                'status' => $status,
                'amount' => $amount,
                'fee' => 50,
                'currency' => 'NGN',
                'bank_name' => 'Test Bank',
                'account_number' => '1234567890',
                'created_at' => now()->toISOString(),
                'complete_message' => $status === 'SUCCESSFUL' ? 'Transfer successful' : 'Transfer failed',
            ]
        ];

        $this->info("📤 Simulating webhook with payload:");
        $this->line(json_encode($webhookPayload, JSON_PRETTY_PRINT));
        $this->newLine();

        // Step 4: Create mock request and process webhook
        $request = new Request();
        $request->replace($webhookPayload);
        $request->headers->set('verif-hash', env('FLW_SECRET_HASH'));

        $walletService = app(WalletService::class);

        try {
            $this->info("🔄 Processing webhook...");
            $response = $walletService->processWebhook($request);

            $this->info("✅ Webhook processed successfully");
            $this->line("Response: " . json_encode($response->getData(), JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error("❌ Webhook processing failed: " . $e->getMessage());
            return 1;
        }

        // Step 5: Verify results
        $this->newLine();
        $this->info("🔍 Verifying results...");

        $updatedTransaction = $transaction->fresh();
        $updatedUser = $user->fresh();

        $this->table(
            ['Field', 'Before', 'After', 'Status'],
            [
                ['Transaction Status', TransactionStatusEnum::PENDING->value, $updatedTransaction->status, $updatedTransaction->status === ($status === 'SUCCESSFUL' ? TransactionStatusEnum::SUCCESSFUL->value : TransactionStatusEnum::FAILED->value) ? '✅' : '❌'],
                ['Wallet Balance', $user->wallet->balance ?? 0, $updatedUser->wallet->balance ?? 0, 'ℹ️'],
                ['Transaction Meta', 'Basic', 'Updated with webhook data', !empty($updatedTransaction->meta_data) ? '✅' : '❌'],
            ]
        );

        // Step 6: Summary
        $this->newLine();
        if ($updatedTransaction->status === ($status === 'SUCCESSFUL' ? TransactionStatusEnum::SUCCESSFUL->value : TransactionStatusEnum::FAILED->value)) {
            $this->info("🎉 Test PASSED! Webhook processed correctly.");

            if ($status === 'SUCCESSFUL') {
                $expectedBalance = ($user->wallet->balance ?? 0) - $amount;
                $actualBalance = $updatedUser->wallet->balance ?? 0;

                if ($expectedBalance == $actualBalance) {
                    $this->info("💰 Wallet balance updated correctly");
                } else {
                    $this->warn("⚠️  Wallet balance mismatch. Expected: {$expectedBalance}, Actual: {$actualBalance}");
                }
            }
        } else {
            $this->error("❌ Test FAILED! Transaction status not updated correctly.");
            return 1;
        }

        // Step 7: Cleanup option
        if ($this->confirm('🗑️  Do you want to delete the test transaction?', true)) {
            $updatedTransaction->delete();
            $this->info("✅ Test transaction deleted");
        }

        $this->newLine();
        $this->info("📋 Next steps:");
        $this->line("1. Check logs: tail -f storage/logs/laravel.log");
        $this->line("2. Test via web interface: /test-webhook/simulate-transfer");
        $this->line("3. Configure webhook URL in Flutterwave dashboard");
        $this->line("4. Test with real transactions");

        return 0;
    }
}
