<?php

use App\Models\User;

test('Should logout user succesfully', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'message',
    ]);
});
