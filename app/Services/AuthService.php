<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;

class AuthService
{
    public function login(array $credentials)
    {
        $token = auth()->attempt($credentials);

        if (!$token) {
            throw new InvalidCredentialsException();
        }

        return [
            'token' => $token,
            'user' => auth()->user(),
        ];
    }
}