<?php

namespace App\Jobs;

use App\Models\UserPhysicalProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $profileId;
    public string $style;

    public function __construct(int $profileId, string $style)
    {
        $this->profileId = $profileId;
        $this->style = $style;
    }

    public function handle(): void
    {
        $profile = UserPhysicalProfile::find($this->profileId);
        if (!$profile) {
            return; // silently skip
        }
        // Transition to generating
        $profile->avatar_status = 'generating';
        $profile->save();

        try {
            // Placeholder logic for actual AI image generation.
            // In future: call external service with $profile->avatar_prompt and physical attributes.
            // Simulate work
            usleep(200000); // 200ms placeholder

            // For now produce a deterministic placeholder image URL
            $hash = substr(md5(($profile->avatar_prompt ?? '') . $profile->user_id . $this->style), 0, 12);
            $profile->avatar_image_url = url("/storage/avatars/placeholder_{$hash}.png");
            $profile->avatar_status = 'ready';
            $profile->save();
        } catch (\Throwable $e) {
            Log::error('Avatar generation failed', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            $profile->avatar_status = 'failed';
            $profile->save();
        }
    }
}
