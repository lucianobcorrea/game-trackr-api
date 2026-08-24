<?php

namespace App\Services;

use App\Services\Igdb\IgdbClientService;
use App\Services\Igdb\IgdbGameService;
use App\Services\Igdb\IgdbHomeService;

class IgdbService
{
    public function __construct(
        protected IgdbClientService $client,
        protected IgdbHomeService $homeService,
        protected IgdbGameService $gameService
    ) {}

    /**
     * @return array<mixed>
     */
    public function query(string $endpoint, string $apicalypseQuery): array
    {
        return $this->client->query($endpoint, $apicalypseQuery);
    }

    /**
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
        return $this->homeService->getNewReleases($limit, $page);
    }

    /**
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
    public function getMostAnticipated(int $limit = 10, int $page = 1): array
    {
        return $this->homeService->getMostAnticipated($limit, $page);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getGameBySlug(string $slug): ?array
    {
        return $this->gameService->getGameBySlug($slug);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPlatforms(int $limit = 50): array
    {
        return $this->gameService->getPlatforms($limit);
    }
}
