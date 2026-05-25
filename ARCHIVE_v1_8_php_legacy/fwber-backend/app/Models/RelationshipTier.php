<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $match_id
 * @property string $current_tier
 * @property int $messages_exchanged
 * @property int $days_connected
 * @property bool $has_met_in_person
 * @property \Illuminate\Support\Carbon|null $first_matched_at
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $met_in_person_at
 * @property \Illuminate\Support\Carbon|null $user1_confirmed_meeting_at
 * @property \Illuminate\Support\Carbon|null $user2_confirmed_meeting_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserMatch $match
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereCurrentTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereDaysConnected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereFirstMatchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereHasMetInPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereMatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereMessagesExchanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereMetInPersonAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereUser1ConfirmedMeetingAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RelationshipTier whereUser2ConfirmedMeetingAt($value)
 *
 * @mixin \Eloquent
 */
class RelationshipTier extends Model
{
    protected $fillable = [
        'match_id',
        'current_tier',
        'messages_exchanged',
        'days_connected',
        'has_met_in_person',
        'first_matched_at',
        'last_message_at',
        'met_in_person_at',
        'user1_confirmed_meeting_at',
        'user2_confirmed_meeting_at',
    ];

    protected $casts = [
        'has_met_in_person' => 'boolean',
        'first_matched_at' => 'datetime',
        'last_message_at' => 'datetime',
        'met_in_person_at' => 'datetime',
        'user1_confirmed_meeting_at' => 'datetime',
        'user2_confirmed_meeting_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }

    /**
     * Calculate and update the tier based on current metrics
     */
    public function calculateTier(): string
    {
        if ($this->has_met_in_person) {
            return 'verified';
        }

        if ($this->messages_exchanged >= 50 && $this->days_connected >= 7) {
            return 'established';
        }

        if ($this->messages_exchanged >= 10 && $this->days_connected >= 1) {
            return 'connected';
        }

        if ($this->match) {
            return 'matched';
        }

        return 'discovery';
    }

    /**
     * Update days connected based on first_matched_at
     */
    public function updateDaysConnected(): void
    {
        if ($this->first_matched_at) {
            $this->days_connected = Carbon::parse($this->first_matched_at)->diffInDays(now());
            $this->save();
        }
    }

    /**
     * Increment message count and update tier
     */
    public function incrementMessages(): void
    {
        $this->messages_exchanged++;
        $this->last_message_at = now();

        $newTier = $this->calculateTier();
        $previousTier = $this->current_tier;

        if ($newTier !== $previousTier) {
            $this->current_tier = $newTier;
        }

        $this->save();
    }

    /**
     * Mark as met in person
     */
    public function markMetInPerson(): void
    {
        $this->has_met_in_person = true;
        $this->met_in_person_at = now();
        $this->current_tier = 'verified';
        $this->save();
    }

    /**
     * Confirm meeting for a specific user
     */
    public function confirmMeetingForUser(int $userId): void
    {
        // Ensure match is loaded
        if (! $this->relationLoaded('match')) {
            $this->load('match');
        }

        if ($this->match->user1_id === $userId) {
            $this->user1_confirmed_meeting_at = now();
        } elseif ($this->match->user2_id === $userId) {
            $this->user2_confirmed_meeting_at = now();
        }

        $this->save();

        // If both have confirmed, mark as met in person
        if ($this->user1_confirmed_meeting_at && $this->user2_confirmed_meeting_at) {
            $this->markMetInPerson();
        }
    }

    /**
     * Get tier information for display
     */
    public function getTierInfo(): array
    {
        $tiers = [
            'discovery' => [
                'name' => 'Discovery',
                'icon' => '🔍',
                'color' => 'gray',
                'unlocks' => ['AI-generated photos', 'Basic profile info'],
            ],
            'matched' => [
                'name' => 'Matched',
                'icon' => '💫',
                'color' => 'blue',
                'unlocks' => ['Direct messaging', '1-2 blurred real photos'],
            ],
            'connected' => [
                'name' => 'Connected',
                'icon' => '💬',
                'color' => 'purple',
                'unlocks' => ['3-5 real photos', 'Video chat', 'Voice messages'],
            ],
            'established' => [
                'name' => 'Established',
                'icon' => '❤️',
                'color' => 'pink',
                'unlocks' => ['Full photo gallery', 'Contact sharing'],
            ],
            'verified' => [
                'name' => 'Verified',
                'icon' => '✅',
                'color' => 'green',
                'unlocks' => ['Complete access', 'Private content'],
            ],
        ];

        return $tiers[$this->current_tier] ?? $tiers['discovery'];
    }

    /**
     * Get visible photo counts for current tier
     */
    public function getVisiblePhotoCount(int $totalRealPhotos): array
    {
        switch ($this->current_tier) {
            case 'discovery':
                return ['real' => 0, 'ai' => 999, 'blurred' => 0];

            case 'matched':
                return ['real' => 0, 'ai' => 999, 'blurred' => min(2, $totalRealPhotos)];

            case 'connected':
                return ['real' => min(5, $totalRealPhotos), 'ai' => 999, 'blurred' => 0];

            case 'established':
            case 'verified':
                return ['real' => $totalRealPhotos, 'ai' => 999, 'blurred' => 0];

            default:
                return ['real' => 0, 'ai' => 999, 'blurred' => 0];
        }
    }
}
