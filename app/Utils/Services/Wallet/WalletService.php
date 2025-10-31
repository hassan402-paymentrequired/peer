<?php

namespace App\Utils\Services\Wallet;

use App\Enum\TransactionStatusEnum;
use App\Exceptions\ClientErrorException;
use App\Jobs\SendNotificationJob;
use App\Models\Transaction;
use App\Notifications\WalletFundedNotification;
use App\Notifications\WalletFundingFailedNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WalletService
{
    protected string $secretKey;
    protected string $publicKey;
    protected string $baseUrl;
    protected string $encryptionKey;

    public function __construct()
    {
        $this->secretKey = env('FLW_SECRET_KEY');
        $this->publicKey = env('FLW_PUBLIC_KEY');
        $this->encryptionKey = env('FLW_ENCRYPTION_KEY');
        $this->baseUrl = 'https://api.flutterwave.com/v3';

        if (!$this->secretKey || !$this->publicKey) {
            Log::error('FlutterwaveService: Missing configuration', [
                'secret_key' => $this->secretKey ? 'set' : 'missing',
                'public_key' => $this->publicKey ? 'set' : 'missing',
            ]);
        }
    }

    /**
     * Generate a unique transaction reference
     * @return string
     */
    private function generateTransactionRef(): string
    {
        return 'STA_' . Str::random(16);
    }

    public function initializeWalletFunding($request)
    {
        try {
            $user = $request->user();
            $reference = $this->generateTransactionRef();
            $callbackUrl = route('wallet.callback');

            $data = [
                'tx_ref' => $reference,
                'amount' => $request->amount,
                'currency' => 'NGN',
                'redirect_url' => $callbackUrl,
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phonenumber' => $user->phone ?? '',
                ],
                'customizations' => [
                    'title' => 'Wallet Funding - ' . config('app.name'),
                    'description' => 'Deposit to wallet',
                    'logo' => config('app.url') . '/logo.png',
                ],
                'meta' => [
                    'user_id' => $user->id,
                    'transaction_type' => 'deposit',
                ]
            ];

            $response = Http::withToken($this->secretKey)
                ->post($this->baseUrl . '/payments', $data);

            if (!$response->successful()) {
                throw new Exception('Failed to initialize payment: ' . $response->body());
            }

            $responseData = $response->json();

            // dd($responseData);

            if ($responseData['status'] !== 'success') {
                throw new Exception('Payment initialization failed: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            // Create transaction record
            Transaction::create([
                'transaction_ref' => $reference,
                'action_type' => 'credit',
                'description' => 'Wallet funding',
                'amount' => $request->amount,
                'user_id' => $user->id,
                'status' => TransactionStatusEnum::PENDING->value,
                'meta_data' => json_encode([
                    'flw_ref' => $responseData['data']['tx_ref'] ?? 'not provided',
                    'gateway_response' => $responseData['message'] ?? 'Transaction initialized',
                    'ip_address' => request()->ip(),
                ]),
                'wallet_balance_before' => $user->wallet->balance,
                'wallet_balance_after' => $user->wallet->balance + $request->amount,
            ]);

            return $responseData['data']['link'];
        } catch (\Exception $e) {
            Log::error('FlutterwaveService: Failed to initialize wallet funding', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'amount' => $request->amount ?? null,
            ]);
            throw new Exception('Failed to initialize wallet funding: ' . $e->getMessage());
        }
    }

    public function paymentCallback(string $reference)
    {
        
        $transaction = Transaction::where('transaction_ref', $reference)->first();

        if (!$transaction) {
            throw new ClientErrorException('Transaction not found', 404);
        }

        if ($transaction->status === TransactionStatusEnum::SUCCESSFUL->value) {
            return false;
        }

        // Verify transaction with Flutterwave
        $response = Http::withToken($this->secretKey)
            ->get($this->baseUrl . "/transactions/verify_by_reference", [
                'tx_ref' => $reference
            ]);

        if (!$response->successful()) {
            Log::error('FlutterwaveService: Failed to verify transaction', [
                'reference' => $reference,
                'response' => $response->body()
            ]);
            return false;
        }

        $responseData = $response->json();

        if ($responseData['status'] !== 'success') {
            Log::error('FlutterwaveService: Transaction verification failed', [
                'reference' => $reference,
                'response' => $responseData
            ]);
            return false;
        }

        $data = $responseData['data'];

        switch ($data['status']) {
            case 'successful':
                $this->processSuccessfulPayment($transaction, $data);
                return true;

            case 'failed':
                $transaction->update([
                    'status' => TransactionStatusEnum::FAILED->value,
                    'meta_data' => array_merge(json_decode($transaction->meta_data, true) ?? [], [
                        'flw_transaction_id' => $data['id'],
                        'gateway_ref' => $data['tx_ref'],
                        'gateway_response' => $data['processor_response'] ?? 'Payment failed',
                        'failed_at' => now(),
                    ]),
                ]);

                $user = $transaction->user;
                if ($user->email) {
                    $failureReason = $data['processor_response'] ?? 'Payment failed';
                    SendNotificationJob::dispatch($user, new WalletFundingFailedNotification($transaction->fresh(), $failureReason));
                }
                return false;

            case 'cancelled':
                $transaction->update([
                    'status' => TransactionStatusEnum::CANCELLED->value,
                    'meta_data' => array_merge(json_decode($transaction->meta_data, true) ?? [], [
                        'flw_transaction_id' => $data['id'],
                        'gateway_ref' => $data['tx_ref'],
                        'cancelled_at' => now(),
                    ]),
                ]);
                return false;

            default:
                $transaction->update([
                    'status' => TransactionStatusEnum::FAILED->value,
                    'meta_data' => array_merge(json_decode($transaction->meta_data, true) ?? [], [
                        'flw_transaction_id' => $data['id'],
                        'gateway_ref' => $data['tx_ref'],
                        'unknown_status' => $data['status'],
                        'failed_at' => now(),
                    ]),
                ]);
                return false;
        }
    }




    private function processSuccessfulPayment(Transaction $transaction, array $paymentData)
    {
        // Credit wallet in database transaction
        DB::transaction(function () use ($transaction, $paymentData) {
            $user = $transaction->user;

            $newBalance = (int)$user->wallet->balance + (int)$transaction->amount;

            // Update user wallet balance
            $user->wallet()->increment('balance', $transaction->amount);

            // Update transaction
            $transaction->update([
                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                'wallet_balance_after' => $newBalance,
                'meta_data' => array_merge(json_decode($transaction->meta_data, true) ?? [], [
                    'flw_transaction_id' => $paymentData['id'],
                    'gateway_ref' => $paymentData['tx_ref'],
                    'paid_at' => $paymentData['created_at'],
                    'payment_type' => $paymentData['payment_type'] ?? null,
                    'amount_settled' => $paymentData['amount_settled'] ?? null,
                    'app_fee' => $paymentData['app_fee'] ?? null,
                ]),
            ]);

            // Send notification to user
            if ($user->email) {
                SendNotificationJob::dispatch($user, new WalletFundedNotification($transaction->fresh()));
            }
        });
    }


    public function verifyBankAccount(string $accountNumber, string $bankCode)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->post($this->baseUrl . '/accounts/resolve', [
                    'account_number' => $accountNumber,
                    'account_bank' => $bankCode,
                ]);

            if (!$response->successful()) {
                Log::error('FlutterwaveService: Bank account verification failed', [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                    'response' => $response->body()
                ]);
                return false;
            }

            $data = $response->json();

            if ($data['status'] !== 'success' || !isset($data['data'])) {
                return false;
            }

            return $data['data'];
        } catch (\Throwable $th) {
            Log::error('FlutterwaveService: Bank account verification exception', [
                'error' => $th->getMessage(),
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);
            throw $th;
        }
    }


    public function initiateWithdrawal(array $manualBankDetails)
    {
        try {
            $user = authUser();
            $reference = $this->generateTransactionRef();

            // Create transaction record first
            $transaction = Transaction::create([
                'transaction_ref' => $reference,
                'user_id' => $user->id,
                'action_type' => 'debit',
                'description' => 'Wallet withdrawal to ' . $manualBankDetails['account_name'] . ' - ' . $manualBankDetails['account_number'],
                'amount' => $manualBankDetails['amount'],
                'wallet_balance_before' => $user->wallet->balance,
                'wallet_balance_after' => $user->wallet->balance - $manualBankDetails['amount'],
                'status' => TransactionStatusEnum::PENDING->value,
                'meta_data' => json_encode([
                    'account_name' => $manualBankDetails['account_name'],
                    'account_number' => $manualBankDetails['account_number'],
                    'bank_code' => $manualBankDetails['bank_code'],
                ]),
            ]);

            return $this->initiateTransfer($manualBankDetails, $reference);
        } catch (\Throwable $th) {
            Log::error('FlutterwaveService: Withdrawal initiation failed', [
                'error' => $th->getMessage(),
                'user_id' => $user->id ?? null,
                'amount' => $manualBankDetails['amount'],
            ]);
            throw new Exception('Failed to initiate withdrawal: ' . $th->getMessage(), 500);
        }
    }



    public function initiateTransfer(array $bankDetails, string $reference): array
    {
        try {
            $data = [
                'account_bank' => $bankDetails['bank_code'],
                'account_number' => $bankDetails['account_number'],
                'amount' => $bankDetails['amount'],
                'narration' => 'Wallet withdrawal - ' . config('app.name'),
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => route('wallet.callback'),
                'debit_currency' => 'NGN'
            ];

            $response = Http::withToken($this->secretKey)
                ->post($this->baseUrl . '/transfers', $data);

            if (!$response->successful()) {
                throw new Exception('Failed to initiate transfer: ' . $response->body());
            }

            $responseData = $response->json();

            if ($responseData['status'] !== 'success') {
                throw new Exception('Transfer initiation failed: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            return $responseData['data'];
        } catch (\Throwable $th) {
            Log::error('FlutterwaveService: Transfer initiation failed', [
                'error' => $th->getMessage(),
                'bank_details' => $bankDetails,
                'reference' => $reference,
            ]);
            throw new Exception('Failed to initiate transfer: ' . $th->getMessage());
        }
    }


    public function processWebhook(Request $request)
    {
        // Flutterwave webhook verification
        $signature = $request->header('verif-hash');
        $secretHash = env('FLW_SECRET_HASH'); // You should add this to your .env

        if (!$signature || $signature !== $secretHash) {
            Log::warning('FlutterwaveService: Invalid webhook signature', [
                'received_signature' => $signature,
            ]);
            return false;
        }

        $payload = $request->all();

        if (!isset($payload['event']) || !isset($payload['data'])) {
            Log::warning('FlutterwaveService: Invalid webhook payload', [
                'payload' => $payload,
            ]);
            return false;
        }

        $event = $payload['event'];
        $data = $payload['data'];

        switch ($event) {
            case 'charge.completed':
                $this->handleChargeCompleted($data);
                break;
            case 'transfer.completed':
                $this->handleTransferCompleted($data);
                break;
            default:
                Log::info('FlutterwaveService: Unhandled webhook event', [
                    'event' => $event,
                    'data' => $data,
                ]);
                break;
        }

        return true;
    }


    private function handleChargeCompleted(array $data)
    {
        $reference = $data['tx_ref'] ?? null;

        if (!$reference) {
            Log::warning('FlutterwaveService: Charge completed webhook missing reference', [
                'data' => $data,
            ]);
            return;
        }

        // Verify the transaction to ensure it's legitimate
        $this->paymentCallback($reference);
    }

    private function handleTransferCompleted(array $data)
    {
        Log::info('FlutterwaveService: Transfer completed webhook', [
            'data' => $data,
        ]);

        $reference = $data['reference'] ?? null;

        if (!$reference) {
            Log::warning('FlutterwaveService: Transfer completed webhook missing reference', [
                'data' => $data,
            ]);
            return;
        }

        $transaction = Transaction::where('transaction_ref', $reference)->first();

        if (!$transaction) {
            Log::warning('FlutterwaveService: Transaction not found for transfer webhook', [
                'reference' => $reference,
            ]);
            return;
        }

        if ($transaction->status === TransactionStatusEnum::SUCCESSFUL->value) {
            return;
        }

        $user = $transaction->user;

        if ($data['status'] === 'SUCCESSFUL') {
            $newBalance = (int)$user->wallet->balance - (int)$transaction->amount;

            // Update user wallet balance
            $user->wallet()->decrement('balance', $transaction->amount);

            // Update transaction
            $transaction->update([
                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                'wallet_balance_after' => $newBalance,
                'meta_data' => array_merge(json_decode($transaction->meta_data, true) ?? [], [
                    'flw_transaction_id' => $data['id'],
                    'gateway_ref' => $data['reference'],
                    'completed_at' => $data['created_at'],
                    'bank_name' => $data['bank_name'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                ]),
            ]);
        } else {
            // Handle failed transfer
            $transaction->update([
                'status' => TransactionStatusEnum::FAILED->value,
                'meta_data' => array_merge(json_decode($transaction->meta_data, true) ?? [], [
                    'flw_transaction_id' => $data['id'],
                    'gateway_ref' => $data['reference'],
                    'failed_at' => $data['created_at'],
                    'failure_reason' => $data['complete_message'] ?? 'Transfer failed',
                ]),
            ]);
        }
    }

    public function getBanks()
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->get($this->baseUrl . '/banks/NG');

            if (!$response->successful()) {
                Log::error('FlutterwaveService: Failed to get banks', [
                    'response' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();

            if ($data['status'] !== 'success' || !isset($data['data'])) {
                return [];
            }

            return $data['data'];
        } catch (\Throwable $th) {
            Log::error('FlutterwaveService: Get banks exception', [
                'error' => $th->getMessage(),
            ]);
            return [];
        }
    }
}
