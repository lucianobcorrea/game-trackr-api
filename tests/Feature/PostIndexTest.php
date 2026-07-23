<?php

use App\Models\Community;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can list posts with pagination, search, and comments array', function () {
    $user = User::factory()->create();
    $community = Community::create([
        'title' => 'GamingCommunity',
        'slug' => 'gaming-community',
        'description' => 'A community for gamers',
        'author_id' => $user->id,
    ]);

    $post1 = Post::create([
        'title' => 'Zelda Breath of the Wild Review',
        'slug' => 'zelda-breath-of-the-wild-review',
        'description' => 'Amazing open world game',
        'likes' => 10,
        'community_id' => $community->id,
        'author_id' => $user->id,
    ]);

    $post2 = Post::create([
        'title' => 'Elden Ring Discussion',
        'slug' => 'elden-ring-discussion',
        'description' => 'Challenging boss fights',
        'likes' => 5,
        'community_id' => $community->id,
        'author_id' => $user->id,
    ]);

    PostComment::create([
        'content' => 'I love Zelda!',
        'likes' => 2,
        'post_id' => $post1->id,
        'author_id' => $user->id,
    ]);

    $response = $this->getJson('/api/posts');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'likes',
                    'community_id',
                    'author_id',
                    'author',
                    'community',
                    'media',
                    'comments' => [
                        '*' => [
                            'id',
                            'content',
                            'likes',
                            'post_id',
                            'author_id',
                            'author',
                        ],
                    ],
                ],
            ],
            'per_page',
            'total',
        ]);

    $searchResponse = $this->getJson('/api/posts?search=Zelda');
    $searchResponse->assertStatus(200);
    expect($searchResponse->json('data'))->toHaveCount(1);
    expect($searchResponse->json('data.0.title'))->toBe('Zelda Breath of the Wild Review');
    expect($searchResponse->json('data.0.comments'))->toHaveCount(1);
    expect($searchResponse->json('data.0.comments.0.content'))->toBe('I love Zelda!');
});
