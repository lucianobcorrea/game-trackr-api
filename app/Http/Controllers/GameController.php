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
            $platforms = $this->igdbGameService->getPlatforms(limit: $limit);

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
}
