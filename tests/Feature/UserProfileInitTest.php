<?php

use App\Models\User;

describe('User Profile Initialization', function () {
    it('automatically generates username from name and assigns random profile_color', function () {
        $user = User::create([
            'name' => 'Lucas Dias',
            'email' => 'lucas@example.com',
            'password' => 'password123',
        ]);

        expect($user->username)->toBe('lucasdias');
        expect($user->profile_color)->toBeString();
        expect($user->profile_color)->toMatch('/^#[0-9A-F]{6}$/');
    });

    it('generates unique username with incrementing number on collision', function () {
        $user1 = User::create([
            'name' => 'Lucas Dias',
            'email' => 'lucas1@example.com',
            'password' => 'password123',
        ]);

        $user2 = User::create([
            'name' => 'Lucas Dias',
            'email' => 'lucas2@example.com',
            'password' => 'password123',
        ]);

        $user3 = User::create([
            'name' => 'Lucas Dias',
            'email' => 'lucas3@example.com',
            'password' => 'password123',
        ]);

        expect($user1->username)->toBe('lucasdias');
        expect($user2->username)->toBe('lucasdias1');
        expect($user3->username)->toBe('lucasdias2');
    });

    it('allows custom username and normalizes custom profile color', function () {
        $user = User::create([
            'name' => 'Custom User',
            'email' => 'custom@example.com',
            'password' => 'password123',
            'username' => 'custom_handle',
            'profile_color' => 'ff5733',
        ]);

        expect($user->username)->toBe('custom_handle');
        expect($user->profile_color)->toBe('#FF5733');
    });

    it('returns username and profile_color in user registration response', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Lucas Dias',
            'email' => 'register_lucas@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.username', 'lucasdias');
        expect($response->json('user.profile_color'))->toMatch('/^#[0-9A-F]{6}$/');
    });

    it('fetches profile colors list via api endpoint', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/colors');

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                '*' => ['key', 'name', 'hex'],
            ],
        ]);
        expect(count($response->json('data')))->toBeGreaterThan(0);
    });

    it('allows authenticated user to update their profile_color and username via PATCH or PUT', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/profile', [
                'username' => 'new_username',
                'profile_color' => '3b82f6',
            ]);

        $response->assertOk();
        $response->assertJsonPath('user.username', 'new_username');
        $response->assertJsonPath('user.profile_color', '#3B82F6');
    });
});
