<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhoneRegistrationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(PhoneRegistrationRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            // 'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->wallet()->create();
        $user->setOtp();

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('phone.verification.notice');
    }
}
