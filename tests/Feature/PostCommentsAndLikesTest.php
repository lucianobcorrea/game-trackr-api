<?php

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can toggle like on a post', function () {
    $user = User::factory()->create();
    $community = Community::create([
        'title' => 'Community 1',
        'slug' => 'community-1',
        'author_id' => $user->id,
    ]);

    $post = Post::create([
        'title' => 'Test Post',
        'slug' => 'test-post',
        'community_id' => $community->id,
        'author_id' => $user->id,
    ]);

    // First click: Add like
    $response = $this->actingAs($user, 'api')
        ->postJson("/api/posts/{$post->id}/like");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Post liked successfully',
            'is_liked' => true,
            'likes' => 1,
        ]);

    expect($post->fresh()->likes)->toBe(1);

    // Second click: Remove like
    $unlikeResponse = $this->actingAs($user, 'api')
        ->postJson("/api/posts/{$post->id}/like");

    $unlikeResponse->assertStatus(200)
        ->assertJson([
            'message' => 'Post unliked successfully',
            'is_liked' => false,
            'likes' => 0,
        ]);

    expect($post->fresh()->likes)->toBe(0);
});

test('authenticated user can create a comment, reply, and toggle like on a comment', function () {
    $user = User::factory()->create();
    $community = Community::create([
        'title' => 'Community 2',
        'slug' => 'community-2',
        'author_id' => $user->id,
    ]);

    $post = Post::create([
        'title' => 'Post with Comments',
        'slug' => 'post-with-comments',
        'community_id' => $community->id,
        'author_id' => $user->id,
    ]);

    // Create comment
    $commentResponse = $this->actingAs($user, 'api')
        ->postJson("/api/posts/{$post->id}/comment", [
            'comment' => 'This is a test comment',
        ]);

    $commentResponse->assertStatus(201)
        ->assertJson([
            'message' => 'Comment created successfully',
        ]);

    $commentId = $commentResponse->json('comment.id');
    expect($commentResponse->json('comment.content'))->toBe('This is a test comment');
    expect($commentResponse->json('comment.author_id'))->toBe($user->id);

    // Reply to comment
    $replyResponse = $this->actingAs($user, 'api')
        ->postJson("/api/posts/{$post->id}/comment/{$commentId}/reply", [
            'comment' => 'This is a reply comment',
        ]);

    $replyResponse->assertStatus(201)
        ->assertJson([
            'message' => 'Comment replied successfully',
        ]);

    expect($replyResponse->json('reply.content'))->toBe('This is a reply comment');
    expect($replyResponse->json('reply.parent_id'))->toBe($commentId);

    // Like comment (first click -> add)
    $likeCommentResponse = $this->actingAs($user, 'api')
        ->postJson("/api/posts/{$post->id}/comment/{$commentId}/like");

    $likeCommentResponse->assertStatus(200)
        ->assertJson([
            'message' => 'Comment liked successfully',
            'is_liked' => true,
            'likes' => 1,
        ]);

    // Unlike comment (second click -> remove)
    $unlikeCommentResponse = $this->actingAs($user, 'api')
        ->postJson("/api/posts/{$post->id}/comment/{$commentId}/like");

    $unlikeCommentResponse->assertStatus(200)
        ->assertJson([
            'message' => 'Comment unliked successfully',
            'is_liked' => false,
            'likes' => 0,
        ]);
});
