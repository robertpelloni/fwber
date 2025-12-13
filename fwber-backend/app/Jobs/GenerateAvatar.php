<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Models\User;
use App\Notifications\AvatarGeneratedNotification;
use App\Services\AvatarGenerationService;
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
    public User $user;
    public array $options;

    /**
     * The number of seconds the job can run before timing out.
     * Replicate can take a while.
     *
     * @var int
     */
    public $timeout = 120;

    public function __construct(User $user, array $options)
    {
        $this->user = $user;
        $this->options = $options;
    }

    public function handle(AvatarGenerationService $avatarService): void
    {
        try {
            $result = $avatarService->generateAvatar($this->user, $this->options);

            // For now produce a deterministic placeholder image URL
            $hash = substr(md5(($profile->avatar_prompt ?? '') . $profile->user_id . $this->style), 0, 12);
            $profile->avatar_image_url = url("/storage/avatars/placeholder_{$hash}.png");
            $profile->avatar_status = 'ready';
            $profile->save();
        } catch (\Throwable $e) {
            Log::error('Avatar generation failed', [
                'profile_id' => $profile->id,
            if ($result['success']) {
                // Extract relative path from URL
                $path = 'avatars/' . basename($result['image_url']);

                // Save to photos table
                $photo = new Photo();
                $photo->user_id = $this->user->id;
                $photo->file_path = $path;
                $photo->filename = basename($path);
                $photo->original_filename = 'ai-generated.png';
                $photo->mime_type = 'image/png';
                $photo->is_private = false;
                $photo->is_primary = false;
                $photo->metadata = [
                    'source' => 'ai',
                    'provider' => $result['provider'],
                    'model' => $this->options['model'] ?? null,
                    'style' => $this->options['style'] ?? null,
                ];
                $photo->save();

                // Notify user
                $this->user->notify(new AvatarGeneratedNotification($photo));
            } else {
                Log::error('Avatar generation failed in job', [
                    'user_id' => $this->user->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                // Optionally notify user of failure
            }
        } catch (\Exception $e) {
            Log::error('Avatar generation exception in job', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
