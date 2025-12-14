<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Show the password reset page.
     */
    public function create(Request $request): Response
    {
        $phone = $request->phone ?? $request->query('phone') ?? '';
        
        // Always show the reset password form
        // Users can access this page directly to enter their OTP
        return Inertia::render('auth/reset-password', [
            'phone' => $phone,
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
            'phone' => [
                'required',
                'string',
                'regex:/^0[7-9][0-1][0-9]{8}$/' // Nigerian phone number format
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Find the reset token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('phone', $request->phone)
            ->first();

        if (!$resetRecord) {
            throw ValidationException::withMessages([
                'phone' => ['No password reset request found for this phone number.'],
            ]);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('phone', $request->phone)->delete();
            throw ValidationException::withMessages([
                'otp' => ['This OTP has expired. Please request a new one.'],
            ]);
        }

        // Verify OTP
        // if (!Hash::check($request->otp, $resetRecord->token)) {
        if ($request->otp !== $resetRecord->token) {
            throw ValidationException::withMessages([
                'otp' => ['The OTP you entered is incorrect.'],
            ]);
        }

        // Find user and reset password
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => ['User not found.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        // Delete the reset token
        DB::table('password_reset_tokens')->where('phone', $request->phone)->delete();

        event(new PasswordReset($user));

        return to_route('login')->with('status', __('Your password has been reset successfully!'));
    }
}
