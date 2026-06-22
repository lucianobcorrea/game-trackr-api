<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['google_id' => $googleUser->id],
                [
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Str::random(32),
                ]
            );

            $token = auth()->login($user);

            return redirect(env('FRONTEND_URL') . "/auth/callback?token={$token}");
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL') . "/auth/error");
        }
    }
}
