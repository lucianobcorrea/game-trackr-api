<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserSetupRequest;
use App\Http\Requests\UpdateUserSetupRequest;
use App\Models\User;
use App\Models\UserSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class UserSetupController extends Controller
{
    /**
     * List user setups.
     */
    public function index(): JsonResponse
    {
        $targetUserId = request('user_id');
        $currentUserId = auth()->id();

        if ($targetUserId && (int) $targetUserId !== (int) $currentUserId) {
            $targetUser = User::find($targetUserId);
            if (! $targetUser) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            if ($targetUser->visibility === User::VISIBILITY_PRIVATE) {
                return response()->json([
                    'message' => 'This profile is private.',
                ], 403);
            }

            $userId = (int) $targetUserId;
        } else {
            $userId = $currentUserId;
        }

        $setups = UserSetup::where('user_id', $userId)
            ->with('media')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Setups retrieved successfully.',
            'data' => $setups,
        ], 200);
    }

    /**
     * Get a specific setup.
     */
    public function show(int|string $setupId): JsonResponse
    {
        $setup = UserSetup::with(['media', 'user'])->where('id', $setupId)->first();
        if (! $setup) {
            return response()->json([
                'message' => 'Setup not found.',
            ], 404);
        }

        $currentUserId = auth()->id();
        if ($setup->user_id !== $currentUserId) {
            $owner = $setup->user;
            if ($owner && $owner->visibility === User::VISIBILITY_PRIVATE) {
                return response()->json([
                    'message' => 'This profile is private.',
                ], 403);
            }
        }

        return response()->json([
            'message' => 'Setup retrieved successfully.',
            'data' => $setup,
        ], 200);
    }

    /**
     * Store a new setup with up to 10 images.
     */
    public function store(StoreUserSetupRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $setup = UserSetup::create([
            'user_id' => $user ? $user->id : auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->hasFile('images')) {
            /** @var array<UploadedFile> $images */
            $images = $request->file('images');
            foreach ($images as $image) {
                if ($image instanceof UploadedFile && $image->isValid()) {
                    $setup->addMedia($image)->toMediaCollection('images');
                }
            }
        }

        return response()->json([
            'message' => 'Setup created successfully.',
            'data' => $setup->fresh(['media']),
        ], 201);
    }

    /**
     * Update an existing setup and manage its images.
     */
    public function update(UpdateUserSetupRequest $request, int|string $setupId): JsonResponse
    {
        $setup = UserSetup::where('id', $setupId)->first();
        if (! $setup) {
            return response()->json([
                'message' => 'Setup not found.',
            ], 404);
        }

        if ($setup->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only update your own setups.',
            ], 403);
        }

        $validated = $request->validated();

        $dataToUpdate = [];
        if (isset($validated['title'])) {
            $dataToUpdate['title'] = $validated['title'];
        }
        if (array_key_exists('description', $validated)) {
            $dataToUpdate['description'] = $validated['description'];
        }

        if (! empty($dataToUpdate)) {
            $setup->update($dataToUpdate);
        }

        if (! empty($validated['remove_image_ids'])) {
            foreach ($validated['remove_image_ids'] as $mediaId) {
                $mediaItem = $setup->media()->where('id', $mediaId)->first();
                $mediaItem?->delete();
            }
        }

        if ($request->hasFile('images')) {
            /** @var array<UploadedFile> $images */
            $images = $request->file('images');
            foreach ($images as $image) {
                if ($image instanceof UploadedFile && $image->isValid()) {
                    $setup->addMedia($image)->toMediaCollection('images');
                }
            }
        }

        return response()->json([
            'message' => 'Setup updated successfully.',
            'data' => $setup->fresh(['media']),
        ], 200);
    }

    /**
     * Delete an existing setup and all its associated media.
     */
    public function delete(int|string $setupId): JsonResponse
    {
        $setup = UserSetup::where('id', $setupId)->first();
        if (! $setup) {
            return response()->json([
                'message' => 'Setup not found.',
            ], 404);
        }

        if ($setup->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only delete your own setups.',
            ], 403);
        }

        $setup->clearMediaCollection('images');
        $setup->delete();

        return response()->json([
            'message' => 'Setup deleted successfully.',
        ], 200);
    }

    /**
     * Delete an individual image from a setup.
     */
    public function deleteImage(int|string $setupId, int|string $mediaId): JsonResponse
    {
        $setup = UserSetup::where('id', $setupId)->first();
        if (! $setup) {
            return response()->json([
                'message' => 'Setup not found.',
            ], 404);
        }

        if ($setup->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only manage your own setups.',
            ], 403);
        }

        $media = $setup->media()->where('id', $mediaId)->first();
        if (! $media) {
            return response()->json([
                'message' => 'Image not found in this setup.',
            ], 404);
        }

        $media->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
            'data' => $setup->fresh(['media']),
        ], 200);
    }
}
