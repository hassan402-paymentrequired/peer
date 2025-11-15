<?php

namespace App\Http\Controllers;

use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send test SMS
     */
    public function sendTestSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:160',
        ]);

        try {
            $result = $this->smsService->sendSms(
                $request->phone,
                $request->message
            );

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS sent successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send SMS'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('SMS test failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'SMS service error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SMS balance
     */
    public function getBalance()
    {
        try {
            $balance = $this->smsService->getBalance();

            return response()->json([
                'status' => 'success',
                'balance' => $balance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user phone number
     */
    public function updatePhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^(\+234|234|0)[789][01]\d{8}$/',
        ]);

        $user = $request->user();
        $user->phone = $request->phone;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Phone number updated successfully'
        ]);
    }

    /**
     * Send OTP for phone verification
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^(\+234|234|0)[789][01]\d{8}$/',
        ]);

        try {
            $pinId = $this->smsService->sendOtp($request->phone);

            if ($pinId) {
                // Store pin_id in session for verification
                session(['otp_pin_id' => $pinId, 'otp_phone' => $request->phone]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'OTP sent successfully',
                    'pin_id' => $pinId
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send OTP'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('OTP sending failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'OTP service error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6',
            'pin_id' => 'required|string',
        ]);

        try {
            $verified = $this->smsService->verifyOtp($request->pin_id, $request->pin);

            if ($verified) {
                // Update user's phone as verified
                $phone = session('otp_phone');
                if ($phone) {
                    $user = $request->user();
                    $user->phone = $phone;
                    $user->phone_verified_at = now();
                    $user->save();

                    // Clear session
                    session()->forget(['otp_pin_id', 'otp_phone']);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Phone number verified successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP verification error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sender IDs
     */
    public function getSenderIds()
    {
        try {
            $senderIds = $this->smsService->getSenderIds();

            return response()->json([
                'status' => 'success',
                'sender_ids' => $senderIds
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get sender IDs: ' . $e->getMessage()
            ], 500);
        }
    }
}
