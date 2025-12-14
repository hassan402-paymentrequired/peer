<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhonePasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(PhonePasswordResetRequest $request): RedirectResponse
    {
        $phone = $request->phone;
        
        // Check if user exists
        $user = \App\Models\User::where('phone', $phone)->first();
        
        if (!$user) {
            // Don't reveal if user exists or not for security
            return back()->with('status', __('If this phone number is registered, you will receive an OTP shortly.'));
        }
        
        // Generate 6-digit OTP
        $otp = Str::random(6);
        
        // Store OTP in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $phone],
            [
                'phone' => $phone,
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        $message = "here you go -> {$otp}";
        
        // Send OTP via SMS
        try {
            $smsService = app(\App\Services\SmsService::class);
            $smsService->sendSms($phone, $message, 'sms');
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP: ' . $e->getMessage());
        }
        
        // Redirect to reset password page with phone number
        return redirect()->route('password.reset', ['phone' => $phone])
            ->with('status', __('OTP sent! Please check your phone and enter the code below.'));
    }
}
