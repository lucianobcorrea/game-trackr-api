<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserGameRequest;
use App\Http\Requests\UpdateUserGameRequest;
use App\Models\User;
use App\Models\UserGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserGameController extends Controller
{
    /**
     * List user games with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $targetUserId = $request->input('user_id');
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
            $userId = (int) $currentUserId;
        }

        $status = $request->input('status');
        $search = $request->input('search');
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $sortBy = (string) $request->input('sort_by', 'created_at');
        $order = strtolower((string) $request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'hours_played', 'rating', 'created_at', 'status'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query = UserGame::query()
            ->where('user_id', $userId)
            ->when($status && strtolower($status) !== 'all', function ($q) use ($status) {
                $q->where('status', strtolower($status));
            })
            ->when($search && trim((string) $search) !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%'.trim((string) $search).'%');
            })
            ->when($request->has('favorite') || $request->has('is_favorite'), function ($q) use ($request) {
                $isFav = $request->boolean('favorite', $request->boolean('is_favorite'));
                $q->where('is_favorite', $isFav);
            })
            ->orderBy($sortBy, $order);

        $games = $query->paginate($perPage);

        return response()->json([
            'message' => 'User games fetched successfully.',
            'data' => $games->items(),
            'meta' => [
                'current_page' => $games->currentPage(),
                'per_page' => $games->perPage(),
                'total' => $games->total(),
                'last_page' => $games->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get the status breakdown (counts) for user games.
     */
    public function breakdown(Request $request): JsonResponse
    {
        $targetUserId = $request->input('user_id');
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
            $userId = (int) $currentUserId;
        }

        $breakdown = UserGame::getBreakdownForUser($userId);

        return response()->json([
            'message' => 'User games breakdown fetched successfully.',
            'data' => $breakdown,
        ], 200);
    }

    /**
     * Show a single game from the user's library.
     */
    public function show(int|string $id): JsonResponse
    {
        $userGame = UserGame::with('user')->find($id);

        if (! $userGame) {
            return response()->json([
                'message' => 'Game not found in user library.',
            ], 404);
        }

        $currentUserId = auth()->id();
        if ($userGame->user_id !== $currentUserId) {
            $owner = $userGame->user;
            if ($owner && $owner->visibility === User::VISIBILITY_PRIVATE) {
                return response()->json([
                    'message' => 'This profile is private.',
                ], 403);
            }
        }

        return response()->json([
            'message' => 'User game fetched successfully.',
            'data' => $userGame,
        ], 200);
    }

    /**
     * Add a game to the user's library (or update if already exists).
     */
    public function store(StoreUserGameRequest $request): JsonResponse
    {
        $userId = (int) auth()->id();
        $validated = $request->validated();

        $existing = UserGame::where('user_id', $userId)
            ->where('igdb_id', $validated['igdb_id'])
            ->first();

        $isNew = $existing === null;

        $userGame = UserGame::updateOrCreate(
            [
                'user_id' => $userId,
                'igdb_id' => $validated['igdb_id'],
            ],
            [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'cover_url' => $validated['cover_url'] ?? null,
                'rating' => $validated['rating'] ?? null,
                'status' => $validated['status'],
                'hours_played' => $validated['hours_played'] ?? 0,
                'is_favorite' => $validated['is_favorite'] ?? false,
            ]
        );

        return response()->json([
            'message' => $isNew ? 'Game added to library successfully.' : 'Game updated in library successfully.',
            'data' => $userGame,
        ], $isNew ? 201 : 200);
    }

    /**
     * Update an existing game in the user's library.
     */
    public function update(UpdateUserGameRequest $request, int|string $id): JsonResponse
    {
        $userGame = UserGame::find($id);

        if (! $userGame) {
            return response()->json([
                'message' => 'Game not found in user library.',
            ], 404);
        }

        if ($userGame->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only update games from your own library.',
            ], 403);
        }

        $validated = $request->validated();
        $userGame->update($validated);

        return response()->json([
            'message' => 'Game updated successfully.',
            'data' => $userGame->fresh(),
        ], 200);
    }

    /**
     * Remove a game from the user's library.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $userGame = UserGame::find($id);

        if (! $userGame) {
            return response()->json([
                'message' => 'Game not found in user library.',
            ], 404);
        }

        if ($userGame->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only delete games from your own library.',
            ], 403);
        }

        $userGame->delete();

        return response()->json([
            'message' => 'Game removed from library successfully.',
        ], 200);
    }
}
