<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Services\ContentVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JournalPrivacyController extends Controller
{
    public function __construct(
        private readonly ContentVisibilityService $visibilityService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'data' => [
                'default_visibility' => $profile->journal_visibility_default ?? Journal::VISIBILITY_FRIENDS,
                'circle_group_id' => $profile->journal_circle_group_id,
                'available_groups' => $this->visibilityService->getAvailableCircles($user)
                    ->map(fn ($group) => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'privacy' => $group->privacy,
                    ])->values(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_visibility' => ['required', Rule::in(Journal::VISIBILITY_OPTIONS)],
            'circle_group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        $user = $request->user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $circleGroupId = $validated['circle_group_id'] ?? null;

        if ($validated['default_visibility'] === Journal::VISIBILITY_CIRCLE) {
            $group = $this->visibilityService->ensureUserCanUseCircle($user, $circleGroupId);

            if (! $group) {
                return response()->json([
                    'message' => 'Select one of your active groups to save circle visibility.',
                ], 422);
            }
        } else {
            $circleGroupId = null;
        }

        $profile->journal_visibility_default = $validated['default_visibility'];
        $profile->journal_circle_group_id = $circleGroupId;
        $profile->save();

        return $this->show($request);
    }
}
