<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function unauthorized()
    {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function register(CreateUserRequest $request)
    {
        $data = $request->validated();
        User::create($data);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $token = auth()->attempt($credentials);
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'error' => null,
            'token' => $token,
            'user' => auth()->user(),
            'message' => 'User created successfully',
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $token = auth()->attempt($credentials);
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'error' => null,
            'token' => $token,
            'user' => $user,
            'message' => 'Logged in successfully',
        ], 200);
    }

    public function validateToken()
    {
        $user = auth()->user();
        return response()->json([
            'error' => null,
            'user' => $user,
        ], 200);
    }

    public function logout()
    {
        try {
            auth()->logout();
            return response()->json([
                'error' => null,
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function refresh()
    {
        $token = auth()->refresh();
        return response()->json(['token' => $token]);
    }
}
