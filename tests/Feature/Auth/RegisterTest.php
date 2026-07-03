<?php

use App\Models\User;

describe('User register', function () {
    it('Should return a token and user data', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();

        $response->assertJsonStructure([
            'token',
            'user',
            'message',
        ]);
    });

    it('Should check if email is unique', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable();
    });
});
