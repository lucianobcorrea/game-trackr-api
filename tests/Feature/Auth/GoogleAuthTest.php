<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery\MockInterface;

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

    it('redirects to web error url on failure if web', function () {
        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andThrow(new Exception('Google error'));

        $response = $this->get('/api/auth/google/callback');
        $frontendUrl = config('app.frontend_url', 'http://localhost:5174');

        $response->assertRedirect("{$frontendUrl}/auth/error");
    });
});
