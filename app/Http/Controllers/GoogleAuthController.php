<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): SymfonyRedirectResponse
    {
        /** @var AbstractProvider $provider */
        $provider = Socialite::driver('google');

        $state = $request->query('state') ?? ($request->query('platform') === 'mobile' || $request->boolean('is_mobile') ? 'mobile' : 'web');

        return $provider->stateless()->with(['state' => $state])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state');
        $isMobile = $state === 'mobile'
            || $request->query('platform') === 'mobile'
            || $request->boolean('is_mobile');

        $base = $isMobile
            ? 'gametrackr://auth'
            : rtrim((string) config('app.frontend_url', 'http://localhost:5174'), '/') . '/auth';

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

            return redirect("{$base}/callback?token={$token}");
        } catch (Exception) {
            return redirect("{$base}/error");
        }
    }
}

