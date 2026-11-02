<?php

use App\Models\User;
use App\Models\UserGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User Game Library Management', function () {
    it('requires authentication to access user game endpoints', function () {
        $this->getJson('/api/profile/games')->assertUnauthorized();
        $this->getJson('/api/profile/games/breakdown')->assertUnauthorized();
        $this->postJson('/api/profile/games', ['name' => 'Zelda'])->assertUnauthorized();
        $this->getJson('/api/profile/games/1')->assertUnauthorized();
        $this->patchJson('/api/profile/games/1', ['status' => 'completed'])->assertUnauthorized();
        $this->deleteJson('/api/profile/games/1')->assertUnauthorized();
    });

    it('can add a game to the user library', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/games', [
                'igdb_id' => 1905,
                'name' => 'The Witcher 3: Wild Hunt',
                'slug' => 'the-witcher-3-wild-hunt',
                'cover_url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1wyy.jpg',
                'rating' => 93.5,
                'status' => 'playing',
                'hours_played' => 24.5,
                'is_favorite' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.igdb_id', 1905)
            ->assertJsonPath('data.name', 'The Witcher 3: Wild Hunt')
            ->assertJsonPath('data.status', 'playing')
            ->assertJsonPath('data.hours_played', 24.5)
            ->assertJsonPath('data.is_favorite', true)
            ->assertJsonPath('data.rating', 93.5);

        $this->assertDatabaseHas('user_games', [
            'user_id' => $user->id,
            'igdb_id' => 1905,
            'status' => 'playing',
            'is_favorite' => true,
        ]);
    });

    it('updates an existing game if user adds the same igdb_id again', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserGame::factory()->create([
            'user_id' => $user->id,
            'igdb_id' => 1905,
            'status' => 'backlog',
            'hours_played' => 0,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/games', [
                'igdb_id' => 1905,
                'name' => 'The Witcher 3: Wild Hunt',
                'slug' => 'the-witcher-3-wild-hunt',
                'status' => 'playing',
                'hours_played' => 10,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'playing')
            ->assertJsonPath('data.hours_played', 10);

        expect(UserGame::where('user_id', $user->id)->where('igdb_id', 1905)->count())->toBe(1);
    });

    it('validates required fields when adding a game', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/games', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['igdb_id', 'name', 'slug', 'status']);
    });

    it('validates that status must be one of the allowed statuses', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/games', [
                'igdb_id' => 1234,
                'name' => 'Elden Ring',
                'slug' => 'elden-ring',
                'status' => 'in_progress', // invalid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    it('lists user games with pagination and default sorting', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserGame::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        // Another user's game
        $otherUser = User::factory()->create();
        UserGame::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/games');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'user_id', 'igdb_id', 'name', 'slug', 'cover_url', 'rating', 'status', 'hours_played', 'is_favorite'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('filters games by status including all', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserGame::factory()->create(['user_id' => $user->id, 'status' => 'playing']);
        UserGame::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        UserGame::factory()->create(['user_id' => $user->id, 'status' => 'backlog']);
        UserGame::factory()->create(['user_id' => $user->id, 'status' => 'platinum']);
        UserGame::factory()->create(['user_id' => $user->id, 'status' => 'abandoned']);

        // Filter by playing
        $playingResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/games?status=playing');
        $playingResponse->assertOk();
        expect($playingResponse->json('meta.total'))->toBe(1);
        expect($playingResponse->json('data.0.status'))->toBe('playing');

        // Filter by all returns all 5
        $allResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/games?status=all');
        $allResponse->assertOk();
        expect($allResponse->json('meta.total'))->toBe(5);
    });

    it('searches games by title/name', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserGame::factory()->create(['user_id' => $user->id, 'name' => 'God of War Ragnarok']);
        UserGame::factory()->create(['user_id' => $user->id, 'name' => 'Cyberpunk 2077']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/games?search=Ragnarok');

        $response->assertOk();
        expect($response->json('meta.total'))->toBe(1);
        expect($response->json('data.0.name'))->toBe('God of War Ragnarok');
    });

    it('filters games by favorite', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserGame::factory()->create(['user_id' => $user->id, 'is_favorite' => true]);
        UserGame::factory()->create(['user_id' => $user->id, 'is_favorite' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/games?favorite=1');

        $response->assertOk();
        expect($response->json('meta.total'))->toBe(1);
        expect($response->json('data.0.is_favorite'))->toBeTrue();
    });

    it('returns accurate breakdown of games by status', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserGame::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'playing']);
        UserGame::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'completed']);
        UserGame::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'backlog']);
        UserGame::factory()->count(1)->create(['user_id' => $user->id, 'status' => 'platinum']);
        UserGame::factory()->count(0)->create(['user_id' => $user->id, 'status' => 'abandoned']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/games/breakdown');

        $response->assertOk()
            ->assertJsonPath('data.playing', 2)
            ->assertJsonPath('data.completed', 3)
            ->assertJsonPath('data.backlog', 5)
            ->assertJsonPath('data.platinum', 1)
            ->assertJsonPath('data.abandoned', 0);
    });

    it('includes games_breakdown in GET /api/profile/me', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserGame::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'playing']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/me');

        $response->assertOk()
            ->assertJsonPath('user.games_breakdown.playing', 2)
            ->assertJsonPath('user.games_breakdown.completed', 0);
    });

    it('shows a single game from the library', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $game = UserGame::factory()->create([
            'user_id' => $user->id,
            'name' => 'Hollow Knight',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/profile/games/{$game->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $game->id)
            ->assertJsonPath('data.name', 'Hollow Knight');
    });

    it('updates game status, hours, and favorite status', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $game = UserGame::factory()->create([
            'user_id' => $user->id,
            'status' => 'playing',
            'hours_played' => 10,
            'is_favorite' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/profile/games/{$game->id}", [
                'status' => 'completed',
                'hours_played' => 45.5,
                'is_favorite' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.hours_played', 45.5)
            ->assertJsonPath('data.is_favorite', true);

        $this->assertDatabaseHas('user_games', [
            'id' => $game->id,
            'status' => 'completed',
            'hours_played' => 45.5,
            'is_favorite' => true,
        ]);
    });

    it('cannot update another user game', function () {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $token = auth()->login($intruder);

        $game = UserGame::factory()->create([
            'user_id' => $owner->id,
            'status' => 'playing',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/profile/games/{$game->id}", [
                'status' => 'abandoned',
            ]);

        $response->assertStatus(403);
    });

    it('removes a game from the user library', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $game = UserGame::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/profile/games/{$game->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Game removed from library successfully.');

        $this->assertDatabaseMissing('user_games', [
            'id' => $game->id,
        ]);
    });

    it('cannot delete another user game', function () {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $token = auth()->login($intruder);

        $game = UserGame::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/profile/games/{$game->id}");

        $response->assertStatus(403);
    });
});
