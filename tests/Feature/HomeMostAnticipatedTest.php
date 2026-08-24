<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('home most anticipated endpoint returns top hyped future games slider data with normalized cover urls', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([
            [
                'id' => 301,
                'name' => 'GTA VI',
                'slug' => 'grand-theft-auto-vi',
                'summary' => 'The next entry in the Grand Theft Auto series.',
                'first_release_date' => 1767225600,
                'hypes' => 1500,
                'follows' => 3200,
                'cover' => [
                    'id' => 401,
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/co9999.jpg',
                ],
                'platforms' => [
                    ['id' => 167, 'name' => 'PlayStation 5', 'slug' => 'ps5'],
                    ['id' => 169, 'name' => 'Xbox Series X|S', 'slug' => 'series-x'],
                ],
            ],
        ], 200),

        'https://api.igdb.com/v4/games/count' => Http::response([
            'count' => 15,
        ], 200),
    ]);

    $response = $this->getJson('/api/home/most-anticipated');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Most anticipated games fetched successfully.',
            'data' => [
                [
                    'id' => 301,
                    'name' => 'GTA VI',
                    'hypes' => 1500,
                    'cover' => [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co9999.jpg',
                    ],
                ],
            ],
        ]);
});

test('home all most anticipated endpoint returns paginated metadata for infinite scroll', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([
            [
                'id' => 301,
                'name' => 'Future Game 1',
                'slug' => 'future-game-1',
                'first_release_date' => 1767225600,
                'hypes' => 800,
            ],
            [
                'id' => 302,
                'name' => 'Future Game 2',
                'slug' => 'future-game-2',
                'first_release_date' => 1769817600,
                'hypes' => 650,
            ],
        ], 200),

        'https://api.igdb.com/v4/games/count' => Http::response([
            'count' => 30,
        ], 200),
    ]);

    $response = $this->getJson('/api/home/most-anticipated/all?page=1&per_page=2');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'All most anticipated games fetched successfully.',
            'meta' => [
                'page' => 1,
                'per_page' => 2,
                'total' => 30,
                'last_page' => 15,
                'has_more' => true,
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});
