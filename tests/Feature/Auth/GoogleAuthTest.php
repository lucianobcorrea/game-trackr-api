<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

describe('Google Auth', function () {
    it('redirects to Google with state parameter for mobile', function () {
        $response = $this->get('/api/auth/google/redirect?platform=mobile');

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        expect($targetUrl)->toContain('state=mobile');
    });

    it('redirects to frontend URL on successful web callback', function () {
        $googleUser = (new SocialiteUser)->map([
            'id' => '1234567890',
            'name' => 'Web Tester',
            'email' => 'webtester@example.com',
        ]);

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $frontendUrl = config('app.frontend_url', 'http://localhost:5174');

        expect($targetUrl)->toStartWith("{$frontendUrl}/auth/callback?token=");
        expect(User::where('email', 'webtester@example.com')->exists())->toBeTrue();
    });

    it('redirects to deep link on successful mobile callback', function () {
        $googleUser = (new SocialiteUser)->map([
            'id' => '9876543210',
            'name' => 'Mobile Tester',
            'email' => 'mobiletester@example.com',
        ]);

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->get('/api/auth/google/callback?state=mobile');

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');

        expect($targetUrl)->toStartWith('gametrackr://auth/callback?token=');
        expect(User::where('email', 'mobiletester@example.com')->exists())->toBeTrue();
    });

    it('redirects to mobile error url on failure if mobile', function () {
        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andThrow(new Exception('Google error'));

        $response = $this->get('/api/auth/google/callback?state=mobile');

        $response->assertRedirect('gametrackr://auth/error');
    });

    it('links google_id to existing user account if email already exists', function () {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        $googleUser = (new SocialiteUser)->map([
            'id' => '555666777',
            'name' => 'Existing User',
            'email' => 'existing@example.com',
        ]);

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();

        $existingUser->refresh();
        expect($existingUser->google_id)->toBe('555666777');
    });
});
