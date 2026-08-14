<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class IgdbService
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
     * Fetch New Releases from IGDB.
     *
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     meta: array{
     *         page: int,
     *         per_page: int,
     *         total: int,
     *         last_page: int,
     *         has_more: bool
     *     }
     * }
     */
    public function getNewReleases(int $limit = 10, int $page = 1): array
    {
        $page = max(1, $page);
        $limit = max(1, min(50, $limit));
        $offset = ($page - 1) * $limit;
        $currentTimestamp = time();

        $query = 'fields name, slug, summary, first_release_date, total_rating, rating, cover.url, platforms.name, platforms.slug; '
            ."where first_release_date != null & first_release_date <= {$currentTimestamp}; "
            .'sort first_release_date desc; '
            ."limit {$limit}; "
            ."offset {$offset};";

        $games = $this->query('games', $query);

        $normalizedGames = array_map(function (array $game): array {
            return $this->formatGame($game);
        }, $games);

        $countQuery = "where first_release_date != null & first_release_date <= {$currentTimestamp};";
        $countResult = $this->query('games/count', $countQuery);
        $total = (int) ($countResult['count'] ?? count($normalizedGames));

        $lastPage = $limit > 0 ? (int) ceil($total / $limit) : 1;
        $hasMore = ($offset + count($normalizedGames)) < $total;

        return [
            'data' => $normalizedGames,
            'meta' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => $lastPage,
                'has_more' => $hasMore,
            ],
        ];
    }

    /**
     * Format game record and normalize cover image URLs.
     *
     * @param  array<string, mixed>  $game
     * @return array<string, mixed>
     */
    private function formatGame(array $game): array
    {
        if (isset($game['cover']) && is_array($game['cover']) && isset($game['cover']['url'])) {
            $game['cover']['url'] = $this->normalizeImageUrl((string) $game['cover']['url']);
        }

        return $game;
    }

    /**
     * Convert IGDB image URLs to high-res HTTPS URLs.
     */
    private function normalizeImageUrl(string $url, string $size = 't_cover_big'): string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        return str_replace('t_thumb', $size, $url);
    }
}
