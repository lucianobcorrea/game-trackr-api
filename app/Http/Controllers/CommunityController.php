<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommunityRequest;
use App\Models\Community;

class CommunityController extends Controller
{
    public function index()
    {
        $perPage = min((int) request('per_page', 10), 100);
        $communities = Community::with(['author', 'members'])->paginate($perPage);
        return response()->json($communities);
    }

    public function show($communityId)
    {
        $community = Community::with(['author', 'members'])->find($communityId);

        if (!$community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        return response()->json($community);
    }

    public function store(CommunityRequest $request)
    {
        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        $community = Community::create($data);

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $community->addMedia($request->file('avatar'))->toMediaCollection('avatar');
        }

        if ($request->hasFile('cover') && $request->file('cover')->isValid()) {
            $community->addMedia($request->file('cover'))->toMediaCollection('cover');
        }

        $community->members()->attach($request->user()->id);

        return response()->json([
            'error' => null,
            'message' => 'Community created successfully',
        ], 201);
    }

    public function delete($communityId)
    {
        $community = Community::find($communityId);

        if (!$community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        if ($community->author_id !== auth()->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        $community->delete();

        return response()->json([
            'error' => null,
            'message' => 'Community deleted successfully',
        ]);
    }

    public function join($communityId)
    {
        $community = Community::find($communityId);

        if (!$community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        if ($community->members()->where('user_id', auth()->user()->id)->exists()) {
            return response()->json([
                'error' => 'You are already a member of this community',
            ], 400);
        }

        $community->members()->attach(auth()->user()->id);

        return response()->json([
            'error' => null,
            'message' => 'Community joined successfully',
        ]);
    }

    public function leave($communityId)
    {
        $community = Community::find($communityId);

        if (!$community) {
            return response()->json([
                'error' => 'Community not found',
            ], 404);
        }

        if (!$community->members()->where('user_id', auth()->user()->id)->exists()) {
            return response()->json([
                'error' => 'You are not a member of this community',
            ], 400);
        }

        if ($community->author_id === auth()->user()->id) {
            return response()->json([
                'error' => 'You cannot leave this community',
            ], 400);
        }

        $community->members()->detach(auth()->user()->id);

        return response()->json([
            'error' => null,
            'message' => 'Community left successfully',
        ]);
    }
}
