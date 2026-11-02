<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Frame search endpoints backed by the PostgreSQL connection (PG_* in .env).
 */
class FrameSearchController extends Controller
{
    private const CONNECTION = 'pgsql';

    /**
     * GET /api/frames/territories?email=user@example.com
     *
     * Returns the territories a user has access to.
     */
    public function userTerritories(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $territories = DB::connection(self::CONNECTION)
                ->table('territory_access as ta')
                ->join('user_info as ui', 'ta.user_id', '=', 'ui.id')
                ->where('ui.email', $validated['email'])
                ->select('ta.*')
                ->get();

            return response()->json([
                'success' => true,
                'count' => $territories->count(),
                'data' => $territories,
            ]);
        } catch (Throwable $e) {
            Log::error('userTerritories failed', ['email' => $validated['email'], 'error' => $e->getMessage()]);

            return $this->errorResponse('Unable to fetch territories.');
        }
    }

    /**
     * GET /api/frames/search?territory_id=XYZ&search=ray&period=30
     *
     * Calls cvo_hello_framesearch(territory_id, search_pattern, NULL, period).
     */
    public function frameSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'territory_id' => ['required', 'string', 'max:50'],
            'search' => ['required', 'string', 'max:100'],
            'period' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $results = DB::connection(self::CONNECTION)->select(
                'SELECT * FROM cvo_hello_framesearch(?, ?, NULL, ?)',
                [$validated['territory_id'], $validated['search'], (int) $validated['period']]
            );

            return response()->json([
                'success' => true,
                'count' => count($results),
                'data' => $results,
            ]);
        } catch (Throwable $e) {
            Log::error('frameSearch failed', ['params' => $validated, 'error' => $e->getMessage()]);

            return $this->errorResponse('Unable to perform frame search.');
        }
    }

    private function errorResponse(string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
