<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile.
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'user' => $user,
        ], 200);
    }

    /**
     * Get available profile theme colors for mobile/web apps.
     */
    public function colors(): JsonResponse
    {
        return response()->json([
            'message' => 'Profile colors fetched successfully.',
            'data' => User::PROFILE_COLORS,
        ], 200);
    }

    /**
     * Update user profile settings.
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'profile_color' => [
                'sometimes',
                'required',
                'string',
                'max:9',
            ],
        ]);

        if (isset($validated['profile_color'])) {
            $validated['profile_color'] = User::normalizeHexColor($validated['profile_color']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ], 200);
    }
}
