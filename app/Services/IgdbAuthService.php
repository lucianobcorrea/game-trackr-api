<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IgdbAuthService
{
    private string $clientId;

    private string $clientSecret;

    private string $tokenUrl;

    public function __construct()
    {
        $this->clientId = (string) config('services.igdb.client_id', '');
        $this->clientSecret = (string) config('services.igdb.client_secret', '');
        $this->tokenUrl = 'https://id.twitch.tv/oauth2/token';
    }

    /**
     * Authenticate with Twitch OAuth2 using Client Credentials Grant.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function authenticate(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && Cache::has('igdb_oauth_token_data')) {
            /** @var array<string, mixed> $cached */
            $cached = Cache::get('igdb_oauth_token_data');

            return $cached;
        }

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            $errorMessage = $response->json('message') ?? 'Failed to authenticate with Twitch OAuth2 for IGDB API.';

            throw new RuntimeException($errorMessage, $response->status());
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['access_token'])) {
            throw new RuntimeException('Invalid OAuth2 response structure from Twitch.');
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $cacheTtl = max(60, $expiresIn - 300);

        Cache::put('igdb_access_token', $data['access_token'], $cacheTtl);
        Cache::put('igdb_oauth_token_data', $data, $cacheTtl);

        return $data;
    }

    /**
     * Get the cached access token or request a new one if unavailable.
     */
    public function getAccessToken(bool $forceRefresh = false): string
    {
        if (! $forceRefresh && Cache::has('igdb_access_token')) {
            return (string) Cache::get('igdb_access_token');
        }

        $authData = $this->authenticate($forceRefresh);

        return (string) $authData['access_token'];
    }
}
