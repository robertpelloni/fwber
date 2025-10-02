<?php

namespace App\Http\Controllers;

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
                return [
                    "id" => $candidate->getKey(),
                    "name" => $candidate->profile?->display_name ?? $candidate->name,
                    "email" => $candidate->email,
                    "avatar_url" => $candidate->profile?->avatar_url,
                    "bio" => $candidate->profile?->bio,
                    "location_description" => $candidate->profile?->location_description,
                    "compatibility_score" => $this->compatibilityScore($user->getKey(), $candidate->getKey()),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            "matches" => $matches,
        ]);
    }

    private function compatibilityScore(int $userId, int $candidateId): int
    {
        $seed = crc32($userId . ":" . $candidateId);

        return 50 + ($seed % 51);
    }
}
