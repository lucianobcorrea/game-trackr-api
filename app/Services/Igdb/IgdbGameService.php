<?php

namespace App\Services\Igdb;

class IgdbGameService
{
    public function __construct(
        protected IgdbClientService $client
    ) {}

    /**
     * Get comprehensive game details by slug.
     *
     * @return array<string, mixed>|null
     */
    public function getGameBySlug(string $slug): ?array
    {
        $escapedSlug = addslashes($slug);

        $query = 'fields name, slug, summary, storyline, first_release_date, status, category, '
            .'rating, rating_count, aggregated_rating, aggregated_rating_count, total_rating, total_rating_count, hypes, follows, '
            .'cover.url, cover.width, cover.height, '
            .'artworks.url, artworks.width, artworks.height, '
            .'screenshots.url, screenshots.width, screenshots.height, '
            .'videos.name, videos.video_id, '
            .'genres.name, genres.slug, '
            .'platforms.name, platforms.slug, platforms.abbreviation, platforms.platform_logo.url, '
            .'game_modes.name, game_modes.slug, '
            .'player_perspectives.name, player_perspectives.slug, '
            .'themes.name, themes.slug, '
            .'involved_companies.developer, involved_companies.publisher, involved_companies.porting, involved_companies.supporting, '
            .'involved_companies.company.name, involved_companies.company.slug, involved_companies.company.logo.url, '
            .'franchise.name, franchise.slug, franchises.name, franchises.slug, '
            .'game_engines.name, game_engines.slug, game_engines.logo.url, '
            .'websites.category, websites.url, '
            .'release_dates.date, release_dates.human, release_dates.platform.name, release_dates.region, '
            .'similar_games.name, similar_games.slug, similar_games.cover.url, similar_games.first_release_date, similar_games.total_rating; '
            ."where slug = \"{$escapedSlug}\"; "
            .'limit 1;';

        /** @var array<int, array<string, mixed>> $games */
        $games = $this->client->query('games', $query);

        if (empty($games)) {
            return null;
        }

        return $this->formatGameDetails($games[0]);
    }

    /**
     * Fetch platforms from IGDB for frontend filters.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPlatforms(int $limit = 50): array
    {
        $query = 'fields name, slug, abbreviation, platform_logo.url; '
            .'where category = (1, 5, 6); '
            .'sort name asc; '
            ."limit {$limit};";

        /** @var array<int, array<string, mixed>> $platforms */
        $platforms = $this->client->query('platforms', $query);

        return array_map(function (array $platform): array {
            if (isset($platform['platform_logo']['url'])) {
                $platform['platform_logo']['url'] = $this->client->normalizeImageUrl((string) $platform['platform_logo']['url'], 't_logo_med');
            }

            return $platform;
        }, $platforms);
    }

    /**
     * Format game details and normalize image/video URLs.
     *
     * @param  array<string, mixed>  $game
     * @return array<string, mixed>
     */
    private function formatGameDetails(array $game): array
    {
        // Cover
        if (isset($game['cover']) && is_array($game['cover']) && isset($game['cover']['url'])) {
            $game['cover']['url'] = $this->client->normalizeImageUrl((string) $game['cover']['url'], 't_cover_big');
        }

        // Artworks
        if (isset($game['artworks']) && is_array($game['artworks'])) {
            $game['artworks'] = array_map(function (array $artwork): array {
                if (isset($artwork['url'])) {
                    $artwork['url'] = $this->client->normalizeImageUrl((string) $artwork['url'], 't_1080p');
                }

                return $artwork;
            }, $game['artworks']);
        }

        // Screenshots
        if (isset($game['screenshots']) && is_array($game['screenshots'])) {
            $game['screenshots'] = array_map(function (array $screenshot): array {
                if (isset($screenshot['url'])) {
                    $screenshot['url'] = $this->client->normalizeImageUrl((string) $screenshot['url'], 't_1080p');
                }

                return $screenshot;
            }, $game['screenshots']);
        }

        // Videos
        if (isset($game['videos']) && is_array($game['videos'])) {
            $game['videos'] = array_map(function (array $video): array {
                if (isset($video['video_id'])) {
                    $videoId = (string) $video['video_id'];
                    $video['youtube_url'] = "https://www.youtube.com/watch?v={$videoId}";
                    $video['embed_url'] = "https://www.youtube.com/embed/{$videoId}";
                }

                return $video;
            }, $game['videos']);
        }

        // Platforms Logos
        if (isset($game['platforms']) && is_array($game['platforms'])) {
            $game['platforms'] = array_map(function (array $platform): array {
                if (isset($platform['platform_logo']['url'])) {
                    $platform['platform_logo']['url'] = $this->client->normalizeImageUrl((string) $platform['platform_logo']['url'], 't_logo_med');
                }

                return $platform;
            }, $game['platforms']);
        }

        // Involved Companies Logos
        if (isset($game['involved_companies']) && is_array($game['involved_companies'])) {
            $game['involved_companies'] = array_map(function (array $item): array {
                if (isset($item['company']['logo']['url'])) {
                    $item['company']['logo']['url'] = $this->client->normalizeImageUrl((string) $item['company']['logo']['url'], 't_logo_med');
                }

                return $item;
            }, $game['involved_companies']);
        }

        // Game Engines Logos
        if (isset($game['game_engines']) && is_array($game['game_engines'])) {
            $game['game_engines'] = array_map(function (array $engine): array {
                if (isset($engine['logo']['url'])) {
                    $engine['logo']['url'] = $this->client->normalizeImageUrl((string) $engine['logo']['url'], 't_logo_med');
                }

                return $engine;
            }, $game['game_engines']);
        }

        // Similar Games Covers
        if (isset($game['similar_games']) && is_array($game['similar_games'])) {
            $game['similar_games'] = array_map(function (array $similarGame): array {
                if (isset($similarGame['cover']['url'])) {
                    $similarGame['cover']['url'] = $this->client->normalizeImageUrl((string) $similarGame['cover']['url'], 't_cover_big');
                }

                return $similarGame;
            }, $game['similar_games']);
        }

        return $game;
    }
}
