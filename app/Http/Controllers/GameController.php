<?php

namespace App\Http\Controllers;

use App\Services\Igdb\IgdbGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GameController extends Controller
{
    public function __construct(
        protected IgdbGameService $igdbGameService
    ) {}

    /**
     * Get detailed game information by slug.
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $game = $this->igdbGameService->getGameBySlug($slug);

            if (! $game) {
                return response()->json([
                    'message' => 'Game not found.',
                ], 404);
            }

            return response()->json([
                'message' => 'Game details fetched successfully.',
                'data' => $game,
            ], 200);
        } catch (Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get platform list for filters.
     */
    public function platforms(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 50);
            $page = (int) $request->input('page', 1);
            $search = $request->input('search');
            $category = $request->input('category');
            $all = $request->boolean('all', false);

            if (is_string($category) && str_contains($category, ',')) {
                $category = array_map('intval', explode(',', $category));
            }

            $platforms = $this->igdbGameService->getPlatforms(
                limit: $limit,
                page: $page,
                search: $search !== null && trim((string) $search) !== '' ? (string) $search : null,
                category: $category !== null ? (is_array($category) ? $category : (int) $category) : null,
                onlyPopular: ! $all
            );

            return response()->json([
                'message' => 'Platforms fetched successfully.',
                'data' => $platforms,
            ], 200);
        } catch (Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Global game search.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', $request->input('limit', 15));
            $search = $request->input('search', $request->input('query'));
            $platform = $request->input('platform', $request->input('platforms'));

            $searchStr = is_string($search) ? $search : null;
            /** @var string|int|array<int, string|int>|null $platformVal */
            $platformVal = (is_string($platform) || is_int($platform) || is_array($platform)) ? $platform : null;

            $result = $this->igdbGameService->searchGames(
                limit: $perPage,
                page: $page,
                search: $searchStr,
                platform: $platformVal
            );

            return response()->json([
                'message' => 'Games searched successfully.',
                'data' => $result['data'],
                'meta' => $result['meta'],
            ], 200);
        } catch (Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
