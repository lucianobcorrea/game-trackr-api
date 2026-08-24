<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityRequest;
use App\Models\Community;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index(): JsonResponse
    {
        $search = request('search');
        $perPage = min((int) request('per_page', 10), 100);
        $communities = Community::query()
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->with(['author', 'members', 'media'])
            ->paginate($perPage);
        $userId = auth()->id();

        $communities->getCollection()->transform(function (Community $community) use ($userId) {
            $community->setAttribute('is_member', $userId
                ? $community->members()
                    ->where('users.id', $userId)
                    ->exists()
                : false);

            return $community;
        });

        return response()->json($communities, 200);
    }

    public function joined(): JsonResponse
    {
        $search = request('search');
        $perPage = min((int) request('per_page', 10), 100);
        $user = auth()->user();
        $userId = $user ? $user->id : 0;

        $communities = Community::query()
            ->whereHas('members', function ($query) use ($userId) {
                $query->where('member_id', $userId);
            })
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->with(['author', 'members', 'media'])
            ->paginate($perPage);

        return response()->json($communities, 200);
    }

    public function show(int|string $communityId): JsonResponse
    {
        $community = Community::with(['author', 'members'])->where('id', $communityId)->first();

        if (! $community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        return response()->json($community);
    }

    public function store(CommunityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $data['author_id'] = $user ? $user->id : auth()->id();
        $data['slug'] = Str::slug((string) $data['title']);
        $data['title'] = str_replace(' ', '', (string) $data['title']);

        $community = Community::create($data);

        if ($request->hasFile('avatar') && $request->file('avatar')?->isValid()) {
            $community->addMedia($request->file('avatar'))->toMediaCollection('avatar');
        }

        if ($request->hasFile('cover') && $request->file('cover')?->isValid()) {
            $community->addMedia($request->file('cover'))->toMediaCollection('cover');
        }

        if ($user) {
            $community->members()->attach($user->id);
        }

        return response()->json([
            'community' => $community,
            'message' => 'Community created successfully',
        ], 201);
    }

    public function delete(int|string $communityId): JsonResponse
    {
        $community = Community::where('id', $communityId)->first();

        if (! $community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        $user = auth()->user();
        if (! $user || $community->author_id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        $community->delete();

        return response()->json([
            'message' => 'Community deleted successfully',
        ]);
    }

    public function join(int|string $communityId): JsonResponse
    {
        $community = Community::where('id', $communityId)->first();

        if (! $community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($community->members()->where('community_members.member_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'You are already a member of this community',
            ], 400);
        }

        $community->members()->attach($user->id);

        return response()->json([
            'message' => 'Community joined successfully',
        ]);
    }

    public function leave(int|string $communityId): JsonResponse
    {
        $community = Community::where('id', $communityId)->first();

        if (! $community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! $community->members()->where('community_members.member_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'You are not a member of this community',
            ], 400);
        }

        if ($community->author_id === $user->id) {
            return response()->json([
                'error' => 'You are the owner, you cannot leave this community',
            ], 400);
        }

        $community->members()->detach($user->id);

        return response()->json([
            'message' => 'Community left successfully',
        ]);
    }
}
