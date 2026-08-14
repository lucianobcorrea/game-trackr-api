<?php

use App\Services\IgdbAuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('igdb auth route successfully authenticates with twitch oauth2', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'access_token' => 'mocked_access_token_12345',
            'expires_in' => 5011200,
            'token_type' => 'bearer',
        ], 200),
    ]);

    $response = $this->postJson('/api/igdb/auth');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Successfully authenticated with Twitch OAuth2 for IGDB API.',
            'data' => [
                'access_token' => 'mocked_access_token_12345',
                'expires_in' => 5011200,
                'token_type' => 'bearer',
            ],
        ]);

    expect(Cache::get('igdb_access_token'))->toBe('mocked_access_token_12345');
});

test('igdb auth service uses cached token on subsequent calls unless force refreshed', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::sequence()
            ->push([
                'access_token' => 'mocked_token_first',
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 200)
            ->push([
                'access_token' => 'mocked_token_second',
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 200),
    ]);

    $service = new IgdbAuthService;
    $token1 = $service->getAccessToken();

    expect($token1)->toBe('mocked_token_first');

    // Without force refresh, returns cached token without making another HTTP request
    $token2 = $service->getAccessToken();
    expect($token2)->toBe('mocked_token_first');

    // With force refresh, triggers second HTTP request in sequence
    $token3 = $service->getAccessToken(forceRefresh: true);
    expect($token3)->toBe('mocked_token_second');
});

test('igdb auth route returns error response on twitch authentication failure', function () {
    Http::fake([
        'https://id.twitch.tv/oauth2/token' => Http::response([
            'message' => 'invalid client secret',
        ], 400),
    ]);

    $response = $this->postJson('/api/igdb/auth');

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'invalid client secret',
        ]);
});
