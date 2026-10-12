<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile.
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $user?->load(['setups.media']);

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
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validated();

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
