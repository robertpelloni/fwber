<?php

namespace App\Http\Controllers;

use App\Http\Resources\MatchResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class MatchController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $matches = User::query()
            ->whereKeyNot($user->getKey())
            ->with("profile")
            ->limit(20)
            ->get()
            ->map(function (User $candidate) use ($user) {
                $candidate->setAttribute(
                    "compatibility_score",
                    $this->compatibilityScore($user->getKey(), $candidate->getKey())
                );

                return $candidate;
            });

        return response()->json([
            "matches" => MatchResource::collection($matches)->toArray(request()),
        ]);
    }

    private function compatibilityScore(int $userId, int $candidateId): int
    {
        $seed = crc32($userId . ":" . $candidateId);

        return 50 + ($seed % 51);
    }
}
