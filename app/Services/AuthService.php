<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthService
{
    /**
     * @param  array<string, mixed>  $credentials
     * @return array{token: string, user: User|Authenticatable|null}
     *
     * @throws InvalidCredentialsException
     */
    public function login(array $credentials): array
    {
        /** @var string|false $token */
        $token = auth()->attempt($credentials);

        if (! $token) {
            throw new InvalidCredentialsException;
        }

        return [
            'token' => $token,
            'user' => auth()->user(),
        ];
    }
}
