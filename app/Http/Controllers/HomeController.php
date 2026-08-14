<?php

namespace App\Http\Controllers;

use App\Services\IgdbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HomeController extends Controller
{
    public function __construct(
        protected IgdbService $igdbService
    ) {}

    /**
     * Get top new releases for home slider.
     */
    public function newReleases(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $result = $this->igdbService->getNewReleases(limit: $limit, page: 1);

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

            $result = $this->igdbService->getNewReleases(limit: $perPage, page: $page);

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
}
