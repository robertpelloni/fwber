<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\User;
use App\Models\UserMatch;
use App\Services\ContentUnlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentUnlockController extends Controller
{
    public function __construct(private readonly ContentUnlockService $contentUnlockService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_type' => 'required|string|in:photo,match_insights',
            'content_id' => 'required',
        ]);

        $user = $request->user()->fresh();
        $contentType = $validated['content_type'];
        $contentId = (string) $validated['content_id'];

        if ($contentType === 'photo') {
            $photo = Photo::query()->findOrFail($contentId);
            $result = $this->contentUnlockService->unlockPhoto($user, $photo);

            return response()->json($result);
        }

        $target = User::query()->findOrFail($contentId);
        $this->assertActiveMatch($user, $target);
        $result = $this->contentUnlockService->unlockMatchInsights($user, $target);

        return response()->json($result);
    }

    private function assertActiveMatch(User $user, User $target): void
    {
        $matched = UserMatch::query()
            ->where(function ($query) use ($user, $target): void {
                $query->where('user1_id', $user->id)->where('user2_id', $target->id);
            })
            ->orWhere(function ($query) use ($user, $target): void {
                $query->where('user1_id', $target->id)->where('user2_id', $user->id);
            })
            ->where('is_active', true)
            ->exists();

        abort_unless($matched, 403, 'You are not matched with this user.');
    }
}
