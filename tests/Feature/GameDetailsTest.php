<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('game details endpoint returns comprehensive game details with normalized media and videos', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([
            [
                'id' => 500,
                'name' => 'Elden Ring',
                'slug' => 'elden-ring',
                'summary' => 'THE NEW FANTASY ACTION RPG.',
                'storyline' => 'Rise, Tarnished, and be guided by grace to brandish the power of the Elden Ring.',
                'first_release_date' => 1645747200,
                'total_rating' => 95.5,
                'cover' => [
                    'id' => 1001,
                    'url' => '//images.igdb.com/igdb/image/upload/t_thumb/co4jni.jpg',
                ],
                'artworks' => [
                    [
                        'id' => 2001,
                        'url' => '//images.igdb.com/igdb/image/upload/t_thumb/ar5qw2.jpg',
                    ],
                ],
                'screenshots' => [
                    [
                        'id' => 3001,
                        'url' => '//images.igdb.com/igdb/image/upload/t_thumb/sc8yz1.jpg',
                    ],
                ],
                'videos' => [
                    [
                        'id' => 4001,
                        'name' => 'Reveal Trailer',
                        'video_id' => 'E3Huy2cdih0',
                    ],
                ],
                'genres' => [
                    ['id' => 12, 'name' => 'Role-playing (RPG)', 'slug' => 'role-playing-rpg'],
                ],
                'platforms' => [
                    [
                        'id' => 6,
                        'name' => 'PC (Microsoft Windows)',
                        'slug' => 'win',
                        'platform_logo' => [
                            'url' => '//images.igdb.com/igdb/image/upload/t_thumb/pl1.jpg',
                        ],
                    ],
                ],
                'similar_games' => [
                    [
                        'id' => 501,
                        'name' => 'Dark Souls III',
                        'slug' => 'dark-souls-iii',
                        'cover' => [
                            'url' => '//images.igdb.com/igdb/image/upload/t_thumb/co1vcf.jpg',
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->getJson('/api/games/elden-ring');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Game details fetched successfully.',
            'data' => [
                'id' => 500,
                'name' => 'Elden Ring',
                'slug' => 'elden-ring',
                'summary' => 'THE NEW FANTASY ACTION RPG.',
                'cover' => [
                    'url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co4jni.jpg',
                ],
                'artworks' => [
                    [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_1080p/ar5qw2.jpg',
                    ],
                ],
                'screenshots' => [
                    [
                        'url' => 'https://images.igdb.com/igdb/image/upload/t_1080p/sc8yz1.jpg',
                    ],
                ],
                'videos' => [
                    [
                        'name' => 'Reveal Trailer',
                        'video_id' => 'E3Huy2cdih0',
                        'youtube_url' => 'https://www.youtube.com/watch?v=E3Huy2cdih0',
                        'embed_url' => 'https://www.youtube.com/embed/E3Huy2cdih0',
                    ],
                ],
                'similar_games' => [
                    [
                        'name' => 'Dark Souls III',
                        'cover' => [
                            'url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1vcf.jpg',
                        ],
                    ],
                ],
            ],
        ]);
});

test('game details endpoint returns 404 when game is not found', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token',
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ], 200),

        'https://api.igdb.com/v4/games' => Http::response([], 200),
    ]);

    $response = $this->getJson('/api/games/non-existent-game');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Game not found.',
        ]);
});
