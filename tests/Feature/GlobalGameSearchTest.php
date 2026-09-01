<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('global search endpoint returns paginated game results filtered by search query and platform', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([
            [
                'id' => 101,
                'name' => 'The Legend of Zelda: Tears of the Kingdom',
                'slug' => 'the-legend-of-zelda-tears-of-the-kingdom',
                'summary' => 'An epic adventure across Hyrule.',
                'first_release_date' => 1683849600,
                'total_rating' => 96.0,
                'cover' => [
                    'id' => 501,
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/co5vmg.jpg',
                ],
                'platforms' => [
                    ['id' => 130, 'name' => 'Nintendo Switch', 'slug' => 'switch'],
                ],
            ],
        ], 200),

        'https://api.igdb.com/v4/games/count' => Http::response([
            'count' => 1,
        ], 200),
    ]);

    $response = $this->getJson('/api/games/search?query=Zelda&platform=switch&page=1&per_page=10');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Games searched successfully.',
            'data' => [
                [
                    'id' => 101,
                    'name' => 'The Legend of Zelda: Tears of the Kingdom',
                    'slug' => 'the-legend-of-zelda-tears-of-the-kingdom',
                    'cover' => [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co5vmg.jpg',
                    ],
                ],
            ],
            'meta' => [
                'page' => 1,
                'per_page' => 10,
                'total' => 1,
                'last_page' => 1,
                'has_more' => false,
            ],
        ]);
});

test('global search handles empty result when no game matches query', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([], 200),
        'https://api.igdb.com/v4/games/count' => Http::response(['count' => 0], 200),
    ]);

    $response = $this->getJson('/api/games/search?search=NonExistentGame');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Games searched successfully.',
            'data' => [],
            'meta' => [
                'total' => 0,
            ],
        ]);
});
