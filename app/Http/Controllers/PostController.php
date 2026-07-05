<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Models\Post;
use App\Support\SlugHelper;

class PostController extends Controller
{
    public function index()
    {

    }

    public function show($postId) {

    }

    public function store(CreatePostRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = SlugHelper::make($data['title'], Post::class);

        $post = Post::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'community_id' => $data['community_id'],
            'author_id' => $request->user()->id,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $post->addMedia($image)->toMediaCollection('images');
                }
            }
        }

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post,
        ], 201);
    }

    public function delete($postId)
    {

    }
}
