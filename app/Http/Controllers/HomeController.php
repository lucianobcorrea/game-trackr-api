<?php

namespace App\Http\Controllers;

use App\Services\Igdb\IgdbHomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HomeController extends Controller
{
    public function __construct(
        protected IgdbHomeService $igdbHomeService
    ) {}

    /**
     * Get top new releases for home slider.
     */
    public function newReleases(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $search = $request->input('search', $request->input('query'));
            $platform = $request->input('platform', $request->input('platforms'));

            $searchStr = is_string($search) ? $search : null;
            /** @var string|int|array<int, string|int>|null $platformVal */
            $platformVal = (is_string($platform) || is_int($platform) || is_array($platform)) ? $platform : null;

            $result = $this->igdbHomeService->getNewReleases(
                limit: $limit,
                page: 1,
                search: $searchStr,
                platform: $platformVal
            );

            return response()->json([
                'message' => 'New releases fetched successfully.',
                'data' => $result['data'],
            ], 200);
        } catch (Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get paginated new releases for view all page (infinite scroll).
     */
    public function allNewReleases(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', $request->input('limit', 15));
            $search = $request->input('search', $request->input('query'));
            $platform = $request->input('platform', $request->input('platforms'));

            $searchStr = is_string($search) ? $search : null;
            /** @var string|int|array<int, string|int>|null $platformVal */
            $platformVal = (is_string($platform) || is_int($platform) || is_array($platform)) ? $platform : null;

            $result = $this->igdbHomeService->getNewReleases(
                limit: $perPage,
                page: $page,
                search: $searchStr,
                platform: $platformVal
            );

            return response()->json([
                'message' => 'All new releases fetched successfully.',
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

    /**
     * Get top most anticipated games for home slider.
     */
    public function mostAnticipated(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $search = $request->input('search', $request->input('query'));
            $platform = $request->input('platform', $request->input('platforms'));

            $searchStr = is_string($search) ? $search : null;
            /** @var string|int|array<int, string|int>|null $platformVal */
            $platformVal = (is_string($platform) || is_int($platform) || is_array($platform)) ? $platform : null;

            $result = $this->igdbHomeService->getMostAnticipated(
                limit: $limit,
                page: 1,
                search: $searchStr,
                platform: $platformVal
            );

            return response()->json([
                'message' => 'Most anticipated games fetched successfully.',
                'data' => $result['data'],
            ], 200);
        } catch (Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get paginated most anticipated games for view all page (infinite scroll).
     */
    public function allMostAnticipated(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', $request->input('limit', 15));
            $search = $request->input('search', $request->input('query'));
            $platform = $request->input('platform', $request->input('platforms'));

            $searchStr = is_string($search) ? $search : null;
            /** @var string|int|array<int, string|int>|null $platformVal */
            $platformVal = (is_string($platform) || is_int($platform) || is_array($platform)) ? $platform : null;

            $result = $this->igdbHomeService->getMostAnticipated(
                limit: $perPage,
                page: $page,
                search: $searchStr,
                platform: $platformVal
            );

            return response()->json([
                'message' => 'All most anticipated games fetched successfully.',
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
