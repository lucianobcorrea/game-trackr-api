<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        /** @var AbstractProvider $provider */
        $provider = Socialite::driver('google');

        return $provider->stateless()->redirect();
    }

    public function callback(): RedirectResponse
    {
        $frontendUrl = (string) config('app.frontend_url', 'http://localhost:5174');

        try {
            /** @var AbstractProvider $provider */
            $provider = Socialite::driver('google');
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = $provider->stateless()->user();

            $user = User::updateOrCreate(
                ['google_id' => $googleUser->getId()],
                [
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Str::random(32),
                ]
            );

            $token = auth()->login($user);

            return redirect("{$frontendUrl}/auth/callback?token={$token}");
        } catch (Exception) {
            return redirect("{$frontendUrl}/auth/error");
        }
    }
}
