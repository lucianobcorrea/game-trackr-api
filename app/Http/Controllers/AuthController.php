<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\LoginRequest;
use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\AuthService;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function unauthorized(): JsonResponse
    {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function register(CreateUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        User::create($data);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $token = auth()->attempt($credentials);
        if (! $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        return response()->json([
            'token' => $token,
            'user' => $user,
            'message' => 'User created successfully',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'token' => $result['token'],
            'user' => $result['user'],
            'message' => 'Logged in successfully',
        ], 200);
    }

    public function validateToken(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'user' => $user,
        ], 200);
    }

    public function logout(): JsonResponse
    {
        try {
            auth()->logout();

            return response()->json([
                'message' => 'Successfully logged out',
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            JWTAuth::setToken(request()->bearerToken());
            $token = JWTAuth::refresh();

            return response()->json(['token' => $token]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $client = $request->input('client', 'web');

        if ($client === 'mobile') {
            $code = (string) random_int(100000, 999999);

            /** @var string $email */
            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            if ($user) {
                PasswordResetCode::updateOrCreate(
                    ['email' => $user->email],
                    [
                        'code' => hash('sha256', $code),
                        'expires_at' => now()->addMinutes(15),
                        'used_at' => null,
                    ]
                );

                Mail::to($user->email)->send(new PasswordResetCodeMail($code));
            }
        } else {
            Password::sendResetLink($request->only('email'));
        }

        return response()->json([
            'message' => 'If the email exists, instructions were sent.',
        ]);
    }

    public function verifyResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required',
        ]);

        /** @var string $email */
        $email = $request->input('email');
        /** @var string $inputCode */
        $inputCode = (string) $request->input('code');

        $record = PasswordResetCode::where('email', $email)->first();

        if (! $record) {
            return response()->json([
                'error' => 'Invalid code',
            ], 400);
        }

        if (! hash_equals($record->code, hash('sha256', $inputCode))) {
            return response()->json([
                'error' => 'Invalid code',
            ], 400);
        }

        if ($record->expires_at->isPast()) {
            return response()->json([
                'error' => 'Code expired',
            ], 400);
        }

        if ($record->used_at != null) {
            return response()->json([
                'error' => 'Code already used',
            ], 400);
        }

        $record->update(['verified_at' => now()]);

        return response()->json([
            'message' => 'Code verified successfully',
        ], 200);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $client = $request->input('client', 'web');

        if ($client === 'mobile') {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:6|confirmed',
            ]);

            /** @var string $email */
            $email = $request->input('email');
            $record = PasswordResetCode::where('email', $email)->first();

            if (! $record) {
                return response()->json(['error' => 'Invalid request'], 400);
            }

            if (! $record->verified_at) {
                return response()->json(['error' => 'Code not verified'], 400);
            }

            if ($record->expires_at->isPast()) {
                return response()->json(['error' => 'Code expired'], 400);
            }

            if ($record->used_at) {
                return response()->json(['error' => 'Code already used'], 400);
            }

            /** @var string $password */
            $password = (string) $request->input('password');

            User::where('email', $email)
                ->update(['password' => bcrypt($password)]);

            $record->update(['used_at' => now()]);

            return response()->json(['message' => 'Password reset successful']);
        } else {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            return $status === Password::PASSWORD_RESET
                ? response()->json(['message' => 'Password reset successfully'], 200)
                : response()->json(['error' => 'Password reset failed'], 500);
        }
    }
}
