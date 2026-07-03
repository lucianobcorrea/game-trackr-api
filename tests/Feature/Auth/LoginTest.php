<?php

use App\Models\User;

describe('User login', function () {
    it('Correct credentials should return a token and user data', function () {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();

        $response->assertJsonStructure([
            'token',
            'user',
            'message',
        ]);
    });

    it('Incorrect credentials should return 401 error', function () {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertUnauthorized();
    });
});
