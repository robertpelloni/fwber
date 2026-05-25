<?php

namespace App\Http\Controllers;

use App\Services\HealthStatusService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        protected HealthStatusService $healthStatusService,
    ) {}

    /**
     * Comprehensive health check endpoint.
     * Returns 200 if all systems healthy, 503 if any critical service is down.
     *
     * @OA\Get(
     *   path="/health",
     *   tags={"Health"},
     *   summary="Comprehensive health check",
     *   description="Checks database, Redis, cache, storage and queue configuration. Returns 503 if any critical service is down.",
     *
     *   @OA\Response(
     *     response=200,
     *     description="All systems healthy",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(property="status", type="string", example="healthy"),
     *       @OA\Property(property="timestamp", type="string", format="date-time"),
     *       @OA\Property(property="version", type="string", example="1.0.0"),
     *       @OA\Property(property="environment", type="string", example="local"),
     *       @OA\Property(property="checks", type="object"),
     *       @OA\Property(property="metrics", type="object")
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=503,
     *     description="One or more services unhealthy",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(property="status", type="string", example="unhealthy")
     *     )
     *   )
     * )
     */
    public function check(): JsonResponse
    {
        $status = $this->healthStatusService->build(includeMetrics: true);
        $httpCode = $status['status'] === 'healthy' ? 200 : 503;

        return response()->json($status, $httpCode);
    }

    /**
     * Simplified liveness probe (for Kubernetes/Docker).
     * Returns 200 if application is running.
     */
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'alive'], 200);
    }

    /**
     * Readiness probe (for Kubernetes/Docker).
     * Returns 200 if critical dependencies required by the active config are ready.
     */
    public function readiness(): JsonResponse
    {
        $status = $this->healthStatusService->build(includeMetrics: false);
        $criticalFailures = collect($status['checks'])
            ->filter(fn (array $check) => ($check['critical'] ?? false) && ($check['status'] ?? 'ok') === 'failed')
            ->toArray();

        if ($criticalFailures === []) {
            return response()->json(['status' => 'ready'], 200);
        }

        return response()->json([
            'status' => 'not_ready',
            'checks' => $criticalFailures,
        ], 503);
    }

    /**
     * Expose infrastructure metrics for scaling decisions.
     */
    public function metrics(): JsonResponse
    {
        return response()->json($this->healthStatusService->buildMetrics());
    }
}
