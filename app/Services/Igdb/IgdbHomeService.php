<?php

namespace App\Services\Igdb;

class IgdbHomeService
{
    public function __construct(
        protected IgdbClientService $client
    ) {}

    /**
     * Fetch New Releases from IGDB.
     *
     * @param  string|int|array<int, string|int>|null  $platform
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
    public function getNewReleases(
        int $limit = 10,
        int $page = 1,
        ?string $search = null,
        string|int|array|null $platform = null
    ): array {
        $page = max(1, $page);
        $limit = max(1, min(50, $limit));
        $offset = ($page - 1) * $limit;
        $currentTimestamp = time();

        $whereClause = $this->buildWhereClause(
            baseCondition: "first_release_date != null & first_release_date <= {$currentTimestamp}",
            search: $search,
            platform: $platform
        );

        $query = 'fields name, slug, summary, first_release_date, total_rating, rating, cover.url, platforms.name, platforms.slug; '
            ."{$whereClause} "
            .'sort first_release_date desc; '
            ."limit {$limit}; "
            ."offset {$offset};";

        /** @var array<int, array<string, mixed>> $games */
        $games = $this->client->query('games', $query);

        $normalizedGames = array_map(function (array $game): array {
            return $this->formatGame($game);
        }, $games);

        $countQuery = $whereClause;
        $countResult = $this->client->query('games/count', $countQuery);
        $total = (int) ($countResult['count'] ?? count($normalizedGames));

        $lastPage = (int) ceil($total / $limit);
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
     * Fetch Most Anticipated games from IGDB.
     *
     * @param  string|int|array<int, string|int>|null  $platform
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
    public function getMostAnticipated(
        int $limit = 10,
        int $page = 1,
        ?string $search = null,
        string|int|array|null $platform = null
    ): array {
        $page = max(1, $page);
        $limit = max(1, min(50, $limit));
        $offset = ($page - 1) * $limit;
        $currentTimestamp = time();

        $whereClause = $this->buildWhereClause(
            baseCondition: "first_release_date != null & first_release_date > {$currentTimestamp}",
            search: $search,
            platform: $platform
        );

        $query = 'fields name, slug, summary, first_release_date, hypes, follows, cover.url, platforms.name, platforms.slug; '
            ."{$whereClause} "
            .'sort hypes desc; '
            ."limit {$limit}; "
            ."offset {$offset};";

        /** @var array<int, array<string, mixed>> $games */
        $games = $this->client->query('games', $query);

        $normalizedGames = array_map(function (array $game): array {
            return $this->formatGame($game);
        }, $games);

        $countQuery = $whereClause;
        $countResult = $this->client->query('games/count', $countQuery);
        $total = (int) ($countResult['count'] ?? count($normalizedGames));

        $lastPage = (int) ceil($total / $limit);
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
     * Build dynamic APICalypse where clause.
     *
     * @param  string|int|array<int, string|int>|null  $platform
     */
    private function buildWhereClause(string $baseCondition, ?string $search = null, string|int|array|null $platform = null): string
    {
        $where = "where {$baseCondition}";

        if ($search !== null && trim($search) !== '') {
            $escapedSearch = addslashes(trim($search));
            $where .= " & name ~ *\"{$escapedSearch}\"*";
        }

        if ($platform !== null) {
            if (is_array($platform)) {
                $platform = array_values(array_filter($platform, fn ($p) => $p !== ''));
                if (! empty($platform)) {
                    $first = $platform[0];
                    if (is_numeric($first)) {
                        $ids = implode(',', array_map('intval', $platform));
                        $where .= " & platforms = ({$ids})";
                    } else {
                        $slugs = implode(',', array_map(fn ($s) => '"'.addslashes((string) $s).'"', $platform));
                        $where .= " & platforms.slug = ({$slugs})";
                    }
                }
            } elseif (is_numeric($platform)) {
                $platformId = (int) $platform;
                $where .= " & platforms = ({$platformId})";
            } elseif (trim((string) $platform) !== '') {
                $platformSlug = addslashes(trim((string) $platform));
                $where .= " & platforms.slug = \"{$platformSlug}\"";
            }
        }

        return $where.';';
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
            $game['cover']['url'] = $this->client->normalizeImageUrl((string) $game['cover']['url']);
        }

        return $game;
    }
}
