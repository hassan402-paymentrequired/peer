<?php

namespace App\Utils\Services\Auth;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{

    public function setUsername(Request $request): void
    {
        // $uploadFolder = 'uploads/avatars';
        $user = Auth::user();
        // if ($image = $request->file('image')) {
        //     $image_uploaded_path = $image->store($uploadFolder, 'public');
        // }
        $user->username = $request->username;
        $user->avatar = $request->avatar;
        $user->save();
    }

    public function adminLogin(Request $request): string|null
    {
        $admin = Admin::where('email', $request->email)->first();
        if (!Hash::check($request->password, $admin->password)) {
            return null;
        }
        return Auth::guard(ADMIN)->login($admin);
    }

    public function logout($guard = API): void
    {
        Auth::guard($guard)->logout();
    }
}
