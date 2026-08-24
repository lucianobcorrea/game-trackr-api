<?php

namespace App\Services\Igdb;

use App\Services\IgdbAuthService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IgdbClientService
{
    private string $baseUrl = 'https://api.igdb.com/v4';

    public function __construct(
        protected IgdbAuthService $igdbAuthService
    ) {}

    /**
     * Fetch raw query from IGDB API endpoint.
     *
     * @return array<mixed>
     *
     * @throws RuntimeException
     */
    public function query(string $endpoint, string $apicalypseQuery): array
    {
        $accessToken = $this->igdbAuthService->getAccessToken();
        $clientId = (string) config('services.igdb.client_id', '');

        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        $response = Http::withHeaders([
            'Client-ID' => $clientId,
            'Authorization' => "Bearer {$accessToken}",
            'Accept' => 'application/json',
        ])
            ->withBody($apicalypseQuery, 'text/plain')
            ->post($url);

        if ($response->failed()) {
            $errorMessage = $response->json('0.title')
                ?? $response->json('message')
                ?? 'IGDB API query failed.';

            throw new RuntimeException($errorMessage, $response->status());
        }

        /** @var array<mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }

    /**
     * Convert IGDB image URLs to high-res HTTPS URLs.
     */
    public function normalizeImageUrl(string $url, string $size = 't_cover_big'): string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        return str_replace('t_thumb', $size, $url);
    }
}
