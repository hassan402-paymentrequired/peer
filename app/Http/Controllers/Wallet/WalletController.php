<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawRequest;
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
        $request->amount
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
    // return dd();
    $result = $this->walletService->paymentCallback($request->get('reference'));

    if (!$result) {
      return to_route('wallet.index')->with('error', 'Payment already verified');
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
      $withdrawalData = $this->walletService->initiateWithdrawal(
        $manualBankDetails
      );
      if (!$withdrawalData) {
        return back()->with('error', 'Unable to initiate withdrawal. Please check the bank details.');
      }
      return back()->with('success', 'Withdrawal initiated successfully. Please wait for confirmation.');
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


  public function VerityTransfer(Request $request)
  {
    try {
      $transferData = $this->walletService->processWebhook($request);

      if (!$transferData) {
        return back()->with('error', 'Unable to verify transfer. Please check the transfer code.');
      }

      return back()->with('data', $transferData);
    } catch (\Exception $e) {
      // dd($e->getMessage());
      Log::error('Transfer verification failed: ' . $e->getMessage());
      return back()->with('error', 'Unable to verify transfer. Please check the transfer code.');
    }
  }
}
