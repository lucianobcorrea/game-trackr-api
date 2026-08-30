<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('platforms endpoint defaults to popular platforms list with normalized logos', function () {
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
                'category' => 6,
                'platform_logo' => [
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/pl1.jpg',
                ],
            ],
            [
                'id' => 167,
                'name' => 'PlayStation 5',
                'slug' => 'ps5',
                'abbreviation' => 'PS5',
                'category' => 1,
            ],
            [
                'id' => 130,
                'name' => 'Nintendo Switch',
                'slug' => 'switch',
                'abbreviation' => 'Switch',
                'category' => 1,
            ],
        ], 200),
    ]);

    $response = $this->getJson('/api/platforms');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Platforms fetched successfully.',
            'data' => [
                [
                    'id' => 6,
                    'name' => 'PC (Microsoft Windows)',
                    'slug' => 'win',
                    'platform_logo' => [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_logo_med/pl1.jpg',
                    ],
                ],
                [
                    'id' => 167,
                    'name' => 'PlayStation 5',
                ],
                [
                    'id' => 130,
                    'name' => 'Nintendo Switch',
                ],
            ],
        ]);
});

test('platforms endpoint supports all=true to fetch all platforms', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/platforms' => Http::response([
            [
                'id' => 1,
                'name' => 'Acorn Archimedes',
                'slug' => 'acorn-archimedes',
            ],
        ], 200),
    ]);

    $response = $this->getJson('/api/platforms?all=true');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Platforms fetched successfully.',
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Acorn Archimedes',
                ],
            ],
        ]);
});

test('platforms endpoint supports search filter', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/platforms' => Http::response([
            [
                'id' => 48,
                'name' => 'PlayStation 4',
                'slug' => 'ps4',
            ],
            [
                'id' => 167,
                'name' => 'PlayStation 5',
                'slug' => 'ps5',
            ],
        ], 200),
    ]);

    $response = $this->getJson('/api/platforms?search=PlayStation');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Platforms fetched successfully.',
            'data' => [
                [
                    'id' => 48,
                    'name' => 'PlayStation 4',
                ],
                [
                    'id' => 167,
                    'name' => 'PlayStation 5',
                ],
            ],
        ]);
});
