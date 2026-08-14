<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('home new releases endpoint returns top games slider data with normalized cover urls', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([
            [
                'id' => 101,
                'name' => 'Elden Ring: Shadow of the Erdtree',
                'slug' => 'elden-ring-shadow-of-the-erdtree',
                'summary' => 'An expansion to Elden Ring.',
                'first_release_date' => 1718928000,
                'cover' => [
                    'id' => 201,
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/co8123.jpg',
                ],
                'platforms' => [
                    ['id' => 6, 'name' => 'PC (Microsoft Windows)', 'slug' => 'win'],
                    ['id' => 48, 'name' => 'PlayStation 4', 'slug' => 'ps4'],
                ],
            ],
            [
                'id' => 102,
                'name' => 'Black Myth: Wukong',
                'slug' => 'black-myth-wukong',
                'summary' => 'An action RPG rooted in Chinese mythology.',
                'first_release_date' => 1724112000,
                'cover' => [
                    'id' => 202,
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/co9456.jpg',
                ],
                'platforms' => [
                    ['id' => 6, 'name' => 'PC (Microsoft Windows)', 'slug' => 'win'],
                ],
            ],
        ], 200),

        'https://api.igdb.com/v4/games/count' => Http::response([
            'count' => 25,
        ], 200),
    ]);

    $response = $this->getJson('/api/home/new-releases');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'New releases fetched successfully.',
            'data' => [
                [
                    'id' => 101,
                    'name' => 'Elden Ring: Shadow of the Erdtree',
                    'cover' => [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co8123.jpg',
                    ],
                ],
                [
                    'id' => 102,
                    'name' => 'Black Myth: Wukong',
                    'cover' => [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co9456.jpg',
                    ],
                ],
            ],
        ]);
});

test('home all new releases endpoint returns paginated metadata for infinite scroll', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([
            [
                'id' => 101,
                'name' => 'Game 1',
                'slug' => 'game-1',
                'first_release_date' => 1718928000,
            ],
            [
                'id' => 102,
                'name' => 'Game 2',
                'slug' => 'game-2',
                'first_release_date' => 1718927000,
            ],
        ], 200),

        'https://api.igdb.com/v4/games/count' => Http::response([
            'count' => 50,
        ], 200),
    ]);

    $response = $this->getJson('/api/home/new-releases/all?page=1&per_page=2');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'All new releases fetched successfully.',
            'meta' => [
                'page' => 1,
                'per_page' => 2,
                'total' => 50,
                'last_page' => 25,
                'has_more' => true,
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});
