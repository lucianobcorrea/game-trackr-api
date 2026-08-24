<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\CreatePostRequest;
use App\Models\Post;
use App\Models\PostComment;
use App\Support\SlugHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class PostController extends Controller
{
    public function index(): JsonResponse
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
        $posts->getCollection()->transform(function (Post $post) use ($userId) {
            $post->setAttribute('is_liked', $userId
                ? $post->likes()->where('user_id', $userId)->exists()
                : false);

            if ($post->relationLoaded('comments')) {
                $post->setRelation('comments', $post->comments->map(function (PostComment $comment) use ($userId) {
                    $comment->setAttribute('is_liked', $userId
                        ? $comment->likes()->where('user_id', $userId)->exists()
                        : false);

                    return $comment;
                })->values());
            }

            return $post;
        });

        return response()->json($posts, 200);
    }

    public function show(int|string $postId): JsonResponse
    {
        $post = Post::with(['author', 'community', 'media', 'comments.author'])->where('id', $postId)->first();
        if (! $post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        $userId = auth()->id();
        $post->setAttribute('is_liked', $userId
            ? $post->likes()->where('user_id', $userId)->exists()
            : false);

        if ($post->relationLoaded('comments')) {
            $post->setRelation('comments', $post->comments->map(function (PostComment $comment) use ($userId) {
                $comment->setAttribute('is_liked', $userId
                    ? $comment->likes()->where('user_id', $userId)->exists()
                    : false);

                return $comment;
            })->values());
        }

        return response()->json($post, 200);
    }

    public function store(CreatePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = SlugHelper::make($data['title'], Post::class);
        $user = $request->user();

        $post = Post::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'community_id' => $data['community_id'],
            'author_id' => $user ? $user->id : auth()->id(),
        ]);

        if ($request->hasFile('images')) {
            /** @var array<UploadedFile> $images */
            $images = $request->file('images');
            foreach ($images as $image) {
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

    public function delete(int|string $postId): JsonResponse
    {
        $post = Post::where('id', $postId)->first();
        if (! $post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        if ($post->author_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only delete your own posts.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ], 200);
    }

    public function like(int|string $postId): JsonResponse
    {
        $post = Post::where('id', $postId)->first();
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

        /** @var Post $freshPost */
        $freshPost = $post->fresh();

        return response()->json([
            'message' => $isLiked ? 'Post liked successfully' : 'Post unliked successfully',
            'is_liked' => $isLiked,
            'likes' => $freshPost->likes,
        ], 200);
    }

    public function likeComment(int|string $postId, int|string $commentId): JsonResponse
    {
        $comment = PostComment::where('post_id', $postId)->where('id', $commentId)->first();
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

        /** @var PostComment $freshComment */
        $freshComment = $comment->fresh();

        return response()->json([
            'message' => $isLiked ? 'Comment liked successfully' : 'Comment unliked successfully',
            'is_liked' => $isLiked,
            'likes' => $freshComment->likes,
        ], 200);
    }

    public function comment(CommentRequest $request, int|string $postId): JsonResponse
    {
        $post = Post::where('id', $postId)->first();
        if (! $post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        $comment = $post->comments()->create([
            'author_id' => auth()->id(),
            'content' => $request->input('comment'),
        ]);

        $comment->load('author');

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $comment,
        ], 201);
    }

    public function commentReply(CommentRequest $request, int|string $postId, int|string $commentId): JsonResponse
    {
        $comment = PostComment::where('post_id', $postId)->where('id', $commentId)->first();
        if (! $comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $reply = $comment->replies()->create([
            'author_id' => auth()->id(),
            'post_id' => (int) $postId,
            'content' => $request->input('comment'),
        ]);

        $reply->load('author');

        return response()->json([
            'message' => 'Comment replied successfully',
            'reply' => $reply,
        ], 201);
    }
}
