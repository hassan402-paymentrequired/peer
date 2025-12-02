<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PhoneVerificationController extends Controller
{
    public function __construct(protected SmsService $smsService)
    {
    }

    /**
     * Show the phone verification page
     */
    public function show(): Response
    {
        $user = authUser();
        
        return Inertia::render('auth/verify-phone', [
            'phone' => $user->phone,
            'isVerified' => $user->hasVerifiedPhone(),
        ]);
    }

    /**
     * Send OTP to user's phone
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $user = authUser();
        
        if ($user->hasVerifiedPhone()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already verified',
            ], 400);
        }

        $pinId = $this->smsService->sendOtp($user->phone);
        
        if (!$pinId) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
            ], 500);
        }

        $deliveryMethod = $this->smsService->getOtpDeliveryMethod($user->phone);
        
        return response()->json([
            'success' => true,
            'message' => "OTP sent successfully via {$deliveryMethod}",
            'delivery_method' => $deliveryMethod,
            'pin_id' => $pinId,
        ]);
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = authUser();
        
        if ($user->hasVerifiedPhone()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already verified',
            ], 400);
        }

        $verified = $this->smsService->verifyOtp($user->phone, $request->otp);
        
        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 400);
        }

        // Mark phone as verified
        $user->phone_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully!',
        ]);
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request): JsonResponse
    {
        return $this->sendOtp($request);
    }
}
