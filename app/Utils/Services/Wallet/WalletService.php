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

class WalletService
{

    protected string $secretKey;
    protected string $publicKey;
    protected string $baseUrl;
    protected string $webhookSecret;
    protected $httpClient;

    public function __construct()
    {

        $this->baseUrl = config('paystack.base_url');
        $this->secretKey = config('paystack.secret_key');
        $this->publicKey = config('paystack.public_key');
        // $this->webhookSecret = config('paystack.webhook_secret');

        if (!$this->secretKey || !$this->publicKey || !$this->baseUrl) {
            Log::error('PaystackService: Missing configuration', [
                'secret_key' => $this->secretKey ? 'set' : 'missing',
                'public_key' => $this->publicKey ? 'set' : 'missing',
                'base_url' => $this->baseUrl,
            ]);
        }

        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Generate a unique transaction reference
     * @return string
     */
    private function generateTransactionRef(): string
    {
        return 'STA_' . Str::random(16);
    }


    public function initializeWalletFunding($amount)
    {
        try {
            $user = request()->user();
            $reference = $this->generateTransactionRef();
            $callbackUrl = route('wallet.callback');

            $response = $this->httpClient->post('/transaction/initialize', [
                'json' => [
                    'amount' => (int)$amount * 100,
                    'email' => $user->email,
                    'reference' => $reference,
                    'callback_url' => $callbackUrl,
                    'currency' => config('paystack.currency', 'NGN'),
                    'metadata' => [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'transaction_type' => 'deposit',
                    ]
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);



            Transaction::create([
                'transaction_ref' => $reference,
                'action_type' => 'credit',
                'description' => 'Wallet funding',
                'amount' => $amount,
                'user_id' => $user->id,
                'status' => TransactionStatusEnum::PENDING->value,
                'meta_data' => json_encode([
                    'gateway_ref' => $data['data']['reference'],
                    'gateway_response' => $data['message'] ?? 'Transaction initialized',
                    'ip_address' => request()->ip(),
                ]),
                'wallet_balance_before' => $user->wallet->balance,
                'wallet_balance_after' => $user->wallet->balance + $amount,
            ]);

            return $data['data']['authorization_url'];
        } catch (\Exception $e) {
            Log::error('WalletService: Failed to initialize wallet funding', [
                'error' => $e->getMessage(),
                'user_id' => auth('web')->id(),
                'amount' => $amount,
            ]);
            dd($e->getMessage());
            return back()->with('error', 'Failed to initialize wallet funding');
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

        $response = $this->httpClient->get("/transaction/verify/{$reference}");

        $data = json_decode($response->getBody()->getContents(), true);

        $data = $data['data'] ?? [];

        // dd($data);

        switch ($data['status']) {
            case 'success':
                $this->processSuccessfulPayment($transaction, $data);
                return true;
            case 'failed':
                $transaction->update([
                    'status' => TransactionStatusEnum::FAILED->value,
                    'meta_data' => array_merge($transaction->meta_data ?? [], [
                        'paystack_transaction_id' => $data['id'],
                        'gateway_ref' => $data['reference'],
                        'gateway_response' => $data['gateway_response'] ?? 'Payment failed',
                        'failed_at' => now(),
                        'ip' => $data['ip_address'] ?? '',
                    ]),
                ]);

                $user = $transaction->user;
                if ($user->email) {
                    $failureReason = $paymentData['gateway_response'] ?? 'Payment failed';
                    SendNotificationJob::dispatch($user, new WalletFundingFailedNotification($transaction->fresh(), $failureReason));
                }

                return false;

            case 'abandoned':
                $transaction->update([
                    'status' => TransactionStatusEnum::CANCELLED->value,
                    'meta_data' => array_merge($transaction->meta_data ?? [], [
                        'paystack_transaction_id' => $data['id'],
                        'gateway_ref' => $data['reference'],
                        'abandoned_at' => now(),
                    ]),
                ]);

                return false;

            case 'pending':
                $transaction->update([
                    'meta_data' => array_merge($transaction->meta_data ?? [], [
                        'paystack_transaction_id' => $data['id'],
                        'gateway_ref' => $data['reference'],
                        'last_checked' => now(),
                    ]),
                ]);

                return false;

            default:
                $transaction->update([
                    'status' => TransactionStatusEnum::FAILED->value,
                    'meta_data' => array_merge($transaction->meta_data ?? [], [
                        'paystack_transaction_id' => $data['id'],
                        'gateway_ref' => $data['reference'],
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
            // dd($user);

            $newBalance = (int)$user->wallet->balance + (int)$transaction->amount;

            // Update user wallet balance
            $user->wallet()->increment('balance', $transaction->amount);

            // Update transaction
            $transaction->update([
                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                'wallet_balance_after' => $newBalance,
                'meta_data' => array_merge(json_decode($transaction->meta_data, true) ?? [], [
                    'paystack_transaction_id' => $paymentData['id'],
                    'gateway_ref' => $paymentData['reference'],
                    'paid_at' => $paymentData['paid_at'],
                    'channel' => $paymentData['channel'] ?? null,
                    'ip_address' => $paymentData['ip_address'] ?? null,
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
            $response = $this->httpClient->get("/bank/resolve", [
                'query' => [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['status'] || !isset($data['data'])) {
                return false;
            }

            return $data['data'];
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function initiateWithdrawal(array $manualBankDetails)
    {
        try {
            $user = auth('web')->user();
            $recipientCode = null;
            $recipientCode = $this->initiateRecipient($manualBankDetails['account_name'], $manualBankDetails['account_number'], $manualBankDetails['bank_code']);

            $reference = $this->generateTransactionRef();

            $transaction = Transaction::create([
                'transaction_ref' => $reference,
                'user_id' => $user->id,
                'action_type' => 'debit',
                'description' => 'Wallet withdrawal to ' . $recipientCode['data']['details']['bank_name'] . ' - ' . $recipientCode['data']['details']['account_number'],
                'amount' => $manualBankDetails['amount'],
                'wallet_balance_before' => $user->wallet->balance,
                'wallet_balance_after' => $user->wallet->balance,
                'status' => TransactionStatusEnum::PENDING->value,
                'meta_data' => $recipientCode['data']['details'],
            ]);

            return $this->initiateTransfer($recipientCode['data']['recipient_code'], $manualBankDetails['amount'], 'Withdrawal from wallet');
        } catch (\Throwable $th) {
            Log::error('WalletService: Withdrawal initiation failed', [
                'error' => $th->getMessage(),
                'user_id' => $user->id ?? null,
                'amount' => $manualBankDetails['amount'],
            ]);
            throw new Exception('Failed to initiate withdrawal: ' . $th->getMessage(), 500);
        }
    }



    public function initiateTransfer(string $recipientCode, float $amount, string $reason): array
    {
        try {
            $response = $this->httpClient->post('/transfer', [
                'json' => [
                    'source' => 'balance',
                    'amount' => $amount * 100,
                    'recipient' => $recipientCode,
                    'reason' => $reason,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['status'] || !isset($data['data'])) {
                throw new ClientErrorException('Failed to initiate transfer: ' . ($data['message'] ?? 'Unknown error'), 400);
            }

            return $data['data'];
        } catch (\Throwable $th) {
            Log::error('PaystackService: Transfer initiation failed', [
                'error' => $th->getMessage(),
                'recipient_code' => $recipientCode,
                'amount' => $amount,
            ]);
            throw new ClientErrorException('Failed to initiate transfer: ' . $th->getMessage(), 500);
        }
    }


    private function initiateRecipient(string $accountName, string $accountNumber, string $bankCode)
    {

        $response = $this->httpClient->post('/transferrecipient', [
            'json' => [
                "type" => "nuban",
                "name" => $accountName,
                "account_number" => $accountNumber,
                "bank_code" => $bankCode,
                "currency" => "NGN"
            ]
        ]);
        return  json_decode($response->getBody()->getContents(), true);
    }


    public function processWebhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');

        if (!$signature || $signature !== hash_hmac('sha512', $request->getContent(), $this->webhookSecret)) {
            Log::warning('PaystackService: Invalid webhook signature', [
                'received_signature' => $signature,
            ]);
            return false;
        }

        $payload = $request->all();

        if (!isset($payload['event']) || !isset($payload['data'])) {
            Log::warning('PaystackService: Invalid webhook payload', [
                'payload' => $payload,
            ]);
            return false;
        }

        $event = $payload['event'];
        $data = $payload['data'];

        switch ($event) {
            case 'transfer.success':
                $this->handleTransferSuccess($data);
                break;
            case 'transfer.failed':
                $this->handleTransferFailed($data);
                break;
            case 'transfer.reversed':
                $this->handleTransferReverse($data);
                break;
            default:
                Log::info('PaystackService: Unhandled webhook event', [
                    'event' => $event,
                    'data' => $data,
                ]);
                break;
        }

        return true;
    }


    private function handleTransferReverse(array $data)
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            Log::warning('PaystackService: Charge success webhook missing reference', [
                'data' => $data,
            ]);
            return;
        }

        $this->paymentCallback($reference);
    }


    private function handleTransferSuccess(array $data)
    {

        Log::info('PaystackService: Transfer success webhook', [
            'data' => $data,
        ]);
    }


    private function handleTransferFailed(array $data)
    {
        Log::info('PaystackService: Transfer failed webhook', [
            'data' => $data,
        ]);
    }
}
