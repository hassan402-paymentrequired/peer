<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawRequest;
use App\Models\WithdrawRequest as ModelsWithdrawRequest;
use App\Utils\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WalletController extends Controller
{

    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index()
    {
        $user = authUser();
        $transactions = $user->transactions()->latest()->get();
        return Inertia::render('wallet/index', [
            'transactions' => $transactions,
        ]);
    }


    public function initializeFunding(Request $request)
    {
        try {
            $authorizeUrl = $this->walletService->initializeWalletFunding(
                $request
            );
            return back()->with('success', $authorizeUrl);
        } catch (\Exception $e) {

            dd(
                $e->getMessage()
            );
            return back()->with('error', $e->getMessage());
        }
    }


    public function paymentCallback(Request $request)
    {
        // For Flutterwave, the reference is passed as tx_ref in the callback
        $reference = $request->get('tx_ref') ?? $request->get('reference');
        $status = $request->get('status');

        if (!$reference) {
            return to_route('wallet.index')->with('error', 'Invalid payment reference');
        }

        // Handle cancelled or failed payments
        if ($status === 'cancelled') {
            return to_route('wallet.index')->with('error', 'Payment was cancelled. Please try again if you wish to fund your wallet.');
        }

        if ($status === 'failed') {
            return to_route('wallet.index')->with('error', 'Payment failed. Please try again or contact support if the issue persists.');
        }

        // Process successful payment
        $result = $this->walletService->paymentCallback($reference);

        if (!$result) {
            return to_route('wallet.index')->with('error', 'Payment verification failed or already processed');
        }

        return to_route('wallet.index')->with('success', 'Payment successful, your wallet has been funded');
    }





    public function initiateWithdrawal(WithdrawRequest $request)
    {
        try {
            $validatedData = $request->validated();


            $manualBankDetails = [
                'bank_code' => $validatedData['bank_code'],
                'account_number' => $validatedData['account_number'],
                'account_name' => $validatedData['account_name'],
                'amount' => $validatedData['amount'],
            ];

            // send withdrawal
            ModelsWithdrawRequest::create([
                'account_number' => $validatedData['account_number'],
                'account_name' => $validatedData['account_name'],
                'amount' => $validatedData['amount'],
                'bank_name' => $validatedData['bank_name']
            ]);


            return back()->with('success', 'Withdrawal request sent successfully. You will get a notification when we verify your reqest');
        } catch (\Exception $e) {
            return null;
        }
    }


    function verifyBankAccount(Request $request)
    {
        try {
            $request->validate([
                'accountNumber' => 'required',
                'bankCode' => 'required',
            ]);

            $accountData = $this->walletService->verifyBankAccount($request->get('accountNumber'), $request->get('bankCode'));

            if (!$accountData) {
                return back()->with('error', 'Unable to verify bank account. Please check the account number.');
            }

            return back()->with('data', $accountData);
        } catch (\Exception $e) {
            // dd($e->getMessage());
            Log::error('Bank account verification failed: ' . $e->getMessage());
            return back()->with('error', 'Unable to verify bank account. Please check the account number.');
        }
    }


    public function processTransferWebhook(Request $request)
    {
        try {
            return $this->walletService->processWebhook($request);
        } catch (\Exception $e) {
            Log::error('Flutterwave webhook processing failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Webhook processing failed'], 500);
        }
    }

    public function verifyTransfer(string $transferId)
    {
        try {
            $transferData = $this->walletService->verifyTransfer($transferId);

            if (!$transferData) {
                return response()->json(['status' => 'error', 'message' => 'Transfer not found'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $transferData]);
        } catch (\Exception $e) {
            Log::error('Transfer verification failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Verification failed'], 500);
        }
    }
}
