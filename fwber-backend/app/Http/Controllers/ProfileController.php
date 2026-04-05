<?php

namespace App\Http\Controllers;

use App\Domain\Core\EventSourcing\EventStore;
use App\Events\Profile\UserProfileCreated;
use App\Events\Profile\UserProfileUpdated;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function __construct(
        private readonly EventStore $eventStore
    ) {}

    /**
     * Get authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = auth()->user()->load('profile');
        if (!$user->profile) {
            return response()->json(['message' => 'Profile not found', 'profile_complete' => false], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
            'profile_complete' => $this->isProfileComplete($user->profile),
        ]);
    }

    /**
     * Get public profile of a user.
     */
    public function showPublic(int $id): JsonResponse
    {
        $user = User::with(['profile', 'photos', 'vouches'])->findOrFail($id);
        return response()->json(['data' => $user]);
    }

    /**
     * Lightweight user search for social/friend surfaces.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $results = User::query()
            ->with('profile')
            ->where('id', '!=', $user->id)
            ->where(function ($builder) use ($query): void {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(12)
            ->get();

        return response()->json($results);
    }

    /**
     * Update user profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        $isNew = !$user->profile;

        // --- EVENT SOURCING ---
        $currentVersion = $this->eventStore->getCurrentVersion((string) $user->id, 'UserProfile');
        $event = $isNew 
            ? new UserProfileCreated((string) $user->id, $validated)
            : new UserProfileUpdated((string) $user->id, $validated);
        
        $this->eventStore->append($event, 'UserProfile', $currentVersion + 1);
        // ----------------------

        // Update profile fields (Projection Update)
        $profile->fill(array_intersect_key($validated, array_flip([
            'display_name',
            'bio',
            'birthdate',
            'gender',
            'pronouns',
            'sexual_orientation',
            'relationship_status',
            'relationship_style',
            'height_cm',
            'body_type',
            'ethnicity',
            'occupation',
            'education',
            'smoking_status',
            'drinking_status',
            'cannabis_status',
            'zodiac_sign',
            'has_children',
            'wants_children',
            'has_pets',
            'interests',
            'looking_for',
            'interested_in',
            'preferences',
            'preferred_language',
            'personality_type',
            'is_incognito',
            'is_confessional_mode',
            'voice_intro_url',
        ])));

        // Handle location object if present
        if (isset($validated['location'])) {
            $profile->latitude = $validated['location']['latitude'] ?? $profile->latitude;
            $profile->longitude = $validated['location']['longitude'] ?? $profile->longitude;
            $profile->location_name = ($validated['location']['city'] ?? '') . ', ' . ($validated['location']['state'] ?? '');
        }

        if (isset($validated['travel_location'])) {
            $profile->travel_latitude = $validated['travel_location']['latitude'] ?? $profile->travel_latitude;
            $profile->travel_longitude = $validated['travel_location']['longitude'] ?? $profile->travel_longitude;
            $profile->travel_location_name = $validated['travel_location']['name'] ?? $profile->travel_location_name;
        }

        $profile->save();

        if (isset($validated['email'])) {
            $user->update(['email' => $validated['email']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->load('profile'),
            'profile_complete' => $this->isProfileComplete($profile),
        ]);
    }

    /**
     * Delete user account.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = auth()->user();
        $userId = $user->id;

        try {
            // --- S3 / LOCAL STORAGE WIPING (DATA MINIMIZATION) ---
            // Actively delete entire media directories to ensure no encrypted 
            // or unencrypted artifacts are left orphaned in object storage.
            \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory("photos/{$userId}");
            \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory("messages/{$userId}");
            \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory("verification/{$userId}");
        } catch (\Exception $e) {
            Log::error("Failed to delete media directories for user {$userId}: " . $e->getMessage());
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'Account deleted successfully']);
    }

    private function isProfileComplete(UserProfile $profile): bool
    {
        return !empty($profile->display_name) && 
               !empty($profile->latitude) && 
               !empty($profile->longitude) &&
               !empty($profile->birthdate);
    }

    /**
     * Get profile completeness (Redirected from DashboardController in routes).
     */
    public function completeness(Request $request): JsonResponse
    {
        return app(DashboardController::class)->getProfileCompleteness($request);
    }
}
