<?php

namespace App\Http\Controllers;

use App\Services\IgdbAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class IgdbAuthController extends Controller
{
    public function __construct(
        protected IgdbAuthService $igdbAuthService
    ) {}

    /**
     * Authenticate with Twitch OAuth2 for IGDB API.
     */
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $forceRefresh = $request->boolean('force_refresh', false);
            $tokenData = $this->igdbAuthService->authenticate($forceRefresh);

            return response()->json([
                'message' => 'Successfully authenticated with Twitch OAuth2 for IGDB API.',
                'data' => $tokenData,
            ], 200);
        } catch (Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
