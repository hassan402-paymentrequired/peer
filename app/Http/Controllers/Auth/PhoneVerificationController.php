<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Exception;
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
        try{
        $request->validate([
            'channel' => 'required|in:sms,whatsapp',
        ]);

        $user = authUser();

        if ($user->hasVerifiedPhone()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already verified',
            ], 400);
        }

        // $message = "Your OTP is " . $user->otp . ". Please enter this code to verify your phone number.";

        $pinId = $this->smsService->sendSms($user->phone, $user->otp, $request->channel);

        if (!$pinId) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
            ], 500);
        }


        return response()->json([
            'success' => true,
            'message' => "OTP sent successfully via {$request->channel}",
            'delivery_method' => $request->channel,
            'pin_id' => $pinId,
        ]);
        }catch(Exception $e)
        {
            return response()->json([
            'success' => false,
            'message' => "Failed to send otp please try again later."
            ]);
        }
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

        $verified = $user->verifyOtp($request->otp);

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 400);
        }

        $user->phone_verified_at = now();
        $user->otp = null;
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
