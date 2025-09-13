<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

function authUser($guard = WEB): User
{
    return Auth::guard($guard)->user();
}


function generateOtp($length = 6)
{
    $numbers = range(0, 9);
    shuffle($numbers);

    return implode(array_slice($numbers, 0, $length));
}

function hasEnoughBalance($amount, $guard = WEB): bool
{
    return AuthUser($guard)->wallet->balance >= $amount;
}


function getUserBalance($guard = WEB)
{
    
    return Auth::guard($guard)->user()->wallet->balance;
}

function decreaseWallet($amount, $guard = WEB)
{
    AuthUser($guard)->wallet()->decrement('balance', $amount);
}
function increaseWallet($amount, $guard = WEB)
{
    AuthUser($guard)->wallet()->increment('balance', $amount);
}

