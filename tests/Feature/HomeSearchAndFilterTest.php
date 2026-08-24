<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('view all new releases supports search by game title', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([
            [
                'id' => 101,
                'name' => 'Elden Ring',
                'slug' => 'elden-ring',
                'first_release_date' => 1645747200,
            ],
        ], 200),

        'https://api.igdb.com/v4/games/count' => Http::response([
            'count' => 1,
        ], 200),
    ]);

    $response = $this->getJson('/api/home/new-releases/all?search=Elden');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'All new releases fetched successfully.',
            'meta' => [
                'total' => 1,
            ],
        ]);

    expect($response->json('data'))->toHaveCount(1);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.igdb.com/v4/games'
            && str_contains($request->body(), 'name ~ *"Elden"*');
    });
});

test('view all most anticipated supports platform filtering by slug or id', function () {
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
                'first_release_date' => 1767225600,
                'hypes' => 1500,
            ],
        ], 200),

        'https://api.igdb.com/v4/games/count' => Http::response([
            'count' => 1,
        ], 200),
    ]);

    $response = $this->getJson('/api/home/most-anticipated/all?platform=ps5');

    $response->assertStatus(200);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.igdb.com/v4/games'
            && str_contains($request->body(), 'platforms.slug = "ps5"');
    });
});

test('platforms endpoint returns list of gaming platforms with normalized logos', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/platforms' => Http::response([
            [
                'id' => 6,
                'name' => 'PC (Microsoft Windows)',
                'slug' => 'win',
                'abbreviation' => 'PC',
                'platform_logo' => [
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/pl1.jpg',
                ],
            ],
            [
                'id' => 167,
                'name' => 'PlayStation 5',
                'slug' => 'ps5',
                'abbreviation' => 'PS5',
                'platform_logo' => [
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/pl2.jpg',
                ],
            ],
        ], 200),
    ]);

    $response = $this->getJson('/api/igdb/platforms');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Platforms fetched successfully.',
            'data' => [
                [
                    'id' => 6,
                    'name' => 'PC (Microsoft Windows)',
                    'platform_logo' => [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_logo_med/pl1.jpg',
                    ],
                ],
                [
                    'id' => 167,
                    'name' => 'PlayStation 5',
                    'platform_logo' => [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_logo_med/pl2.jpg',
                    ],
                ],
            ],
        ]);
});
