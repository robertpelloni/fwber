<?php

namespace App\Http\Controllers;

use App\Models\ProximityArtifact;
use App\Services\ProximityArtifactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProximityArtifactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|integer|min:100|max:10000',
            'type' => 'nullable|in:chat,board_post,announce',
        ]);

        $lat = (float)$request->lat;
        $lng = (float)$request->lng;
        $radius = (int)($request->radius ?? 1000);

        $q = ProximityArtifact::query()->active()->withinBox($lat, $lng, $radius);
        if ($request->filled('type')) {
            $q->type($request->type);
        }

        $artifacts = $q->orderByDesc('created_at')->limit(100)->get()
            ->map(function (ProximityArtifact $a) {
                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'content' => $a->content,
                    'lat' => $a->fuzzed_latitude,
                    'lng' => $a->fuzzed_longitude,
                    'radius' => $a->visibility_radius_m,
                    'expires_at' => $a->expires_at?->toIso8601String(),
                    'moderation_status' => $a->moderation_status,
                    'meta' => $a->meta,
                    'user_id' => $a->user_id,
                ];
            });

        return response()->json(['artifacts' => $artifacts]);
    }

    public function store(Request $request, ProximityArtifactService $service): JsonResponse
    {
        $user = auth()->user();
        $request->validate([
            'type' => 'required|in:chat,board_post,announce',
            'content' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|integer|min:100|max:10000',
        ]);

        try {
            $artifact = $service->createArtifact($user, [
                'type' => $request->type,
                'content' => $request->content,
                'location_lat' => (float)$request->lat,
                'location_lng' => (float)$request->lng,
                'visibility_radius_m' => (int)($request->radius ?? 1000),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['artifact' => [
            'id' => $artifact->id,
            'type' => $artifact->type,
            'content' => $artifact->content,
            'lat' => $artifact->fuzzed_latitude,
            'lng' => $artifact->fuzzed_longitude,
            'radius' => $artifact->visibility_radius_m,
            'expires_at' => $artifact->expires_at?->toIso8601String(),
            'moderation_status' => $artifact->moderation_status,
            'meta' => $artifact->meta,
            'user_id' => $artifact->user_id,
        ]], 201);
    }

    public function show(int $id): JsonResponse
    {
        $artifact = ProximityArtifact::active()->findOrFail($id);
        return response()->json(['artifact' => [
            'id' => $artifact->id,
            'type' => $artifact->type,
            'content' => $artifact->content,
            'lat' => $artifact->fuzzed_latitude,
            'lng' => $artifact->fuzzed_longitude,
            'radius' => $artifact->visibility_radius_m,
            'expires_at' => $artifact->expires_at?->toIso8601String(),
            'moderation_status' => $artifact->moderation_status,
            'meta' => $artifact->meta,
            'user_id' => $artifact->user_id,
        ]]);
    }

    public function flag(int $id, Request $request, ProximityArtifactService $service): JsonResponse
    {
        $user = auth()->user();
        $artifact = ProximityArtifact::findOrFail($id);
        $service->flagArtifact($artifact, $user);
        return response()->json(['message' => 'Flag recorded']);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        $artifact = ProximityArtifact::findOrFail($id);
        if ($artifact->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $artifact->moderation_status = 'removed';
        $artifact->save();
        return response()->json(['message' => 'Removed']);
    }
}
