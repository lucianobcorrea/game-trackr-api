<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\CreatePostRequest;
use App\Models\Post;
use App\Models\PostComment;
use App\Support\SlugHelper;

class PostController extends Controller
{
    public function index()
    {
        $search = request('search');
        $perPage = min((int) request('per_page', 10), 100);

        $posts = Post::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(request('community_id'), fn ($query, $communityId) => $query->where('community_id', $communityId))
            ->with(['author', 'community', 'media', 'comments.author'])
            ->latest()
            ->paginate($perPage);

        $userId = auth()->id();
        $posts->getCollection()->transform(function ($post) use ($userId) {
            $post->is_liked = $userId
                ? $post->likes()->where('user_id', $userId)->exists()
                : false;

            $post->comments = $post->comments ? $post->comments->map(function ($comment) use ($userId) {
                $comment->is_liked = $userId
                    ? $comment->likes()->where('user_id', $userId)->exists()
                    : false;

                return $comment;
            })->values() : [];

            return $post;
        });

        return response()->json($posts, 200);
    }

    public function show($postId)
    {
        $post = Post::with(['author', 'community', 'media', 'comments.author'])->find($postId);
        if (! $post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        $userId = auth()->id();
        $post->is_liked = $userId
            ? $post->likes()->where('user_id', $userId)->exists()
            : false;

        $post->comments = $post->comments ? $post->comments->map(function ($comment) use ($userId) {
            $comment->is_liked = $userId
                ? $comment->likes()->where('user_id', $userId)->exists()
                : false;

            return $comment;
        })->values() : [];

        return response()->json($post, 200);
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
        $post = Post::find($postId);
        if (! $post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        if ($post->author_id != auth()->id()) {
            return response()->json([
                'message' => 'You can only delete your own posts.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ], 200);
    }

    public function like($postId)
    {
        $post = Post::find($postId);
        if (! $post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        $userId = auth()->id();
        $like = $post->likes()->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
            $post->decrement('likes');
            $isLiked = false;
        } else {
            $post->likes()->create([
                'user_id' => $userId,
            ]);
            $post->increment('likes');
            $isLiked = true;
        }

        return response()->json([
            'message' => $isLiked ? 'Post liked successfully' : 'Post unliked successfully',
            'is_liked' => $isLiked,
            'likes' => $post->fresh()->likes,
        ], 200);
    }

    public function likeComment($postId, $commentId)
    {
        $comment = PostComment::where('post_id', $postId)->find($commentId);
        if (! $comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $userId = auth()->id();
        $like = $comment->likes()->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
            $comment->decrement('likes');
            $isLiked = false;
        } else {
            $comment->likes()->create([
                'user_id' => $userId,
            ]);
            $comment->increment('likes');
            $isLiked = true;
        }

        return response()->json([
            'message' => $isLiked ? 'Comment liked successfully' : 'Comment unliked successfully',
            'is_liked' => $isLiked,
            'likes' => $comment->fresh()->likes,
        ], 200);
    }

    public function comment(CommentRequest $request, $postId)
    {
        $post = Post::find($postId);
        if (! $post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        $comment = $post->comments()->create([
            'author_id' => auth()->id(),
            'content' => $request->comment,
        ]);

        $comment->load('author');

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $comment,
        ], 201);
    }

    public function commentReply(CommentRequest $request, $postId, $commentId)
    {
        $comment = PostComment::where('post_id', $postId)->find($commentId);
        if (! $comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $reply = $comment->replies()->create([
            'author_id' => auth()->id(),
            'post_id' => (int) $postId,
            'content' => $request->comment,
        ]);

        $reply->load('author');

        return response()->json([
            'message' => 'Comment replied successfully',
            'reply' => $reply,
        ], 201);
    }
}
