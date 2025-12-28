<?php

namespace App\Http\Controllers;

use App\Models\ViralContent;
use App\Notifications\PushMessage;
use App\Services\AchievementService;
use Illuminate\Http\Request;

class ViralContentController extends Controller
{
    protected $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * Retrieve viral content by its UUID.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $content = ViralContent::with('user')->where('id', $id)->firstOrFail();

        // Increment views if not the owner
        $viewerId = auth('sanctum')->id();
        
        // Simple view counting (can be improved with IP tracking to prevent spam)
        if (!$viewerId || $viewerId !== $content->user_id) {
            $content->increment('views');
            
            // Check for reward (5 views)
            // Reload to get updated views count
            if ($content->fresh()->views >= 5 && !$content->reward_claimed) {
                $owner = $content->user;
                if ($owner) {
                    // Grant 24 hours of Gold
                    $currentExpiry = $owner->tier_expires_at ?: now();
                    if ($currentExpiry->isPast()) {
                        $currentExpiry = now();
                    }
                    
                    $owner->update([
                        'tier' => 'gold',
                        'tier_expires_at' => $currentExpiry->addHours(24),
                    ]);
                    
                    $content->update(['reward_claimed' => true]);

                    try {
                        $this->achievementService->checkAndUnlock($owner, 'viral_views', $content->views);
                    } catch (\Exception $e) {
                        // Ignore
                    }

                    try {
                        $owner->notify(new PushMessage(
                            "Viral Gold Unlocked! 🏆",
                            "Your content is taking off! You've earned 24h of Gold status.",
                            "/profile",
                            "reward"
                        ));
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
            }
        }

        return response()->json([
            'type' => $content->type,
            'content' => $content->content,
            'created_at' => $content->created_at,
            'views' => $content->views,
            'is_owner' => $viewerId === $content->user_id,
            'reward_claimed' => $content->reward_claimed,
            'user_name' => $content->user ? $content->user->name : 'Anonymous',
        ]);
    }
}
