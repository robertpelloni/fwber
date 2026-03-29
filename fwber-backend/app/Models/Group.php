<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $chatroom_id
 * @property string $name
 * @property string|null $description
 * @property array<array-key, mixed>|null $tags
 * @property string|null $category
 * @property string|null $icon
 * @property string $privacy
 * @property string $visibility
 * @property int|null $created_by_user_id
 * @property int|null $creator_id
 * @property int $member_count
 * @property int $max_members
 * @property bool $is_active
 * @property bool $matching_enabled
 * @property float|null $location_lat
 * @property float|null $location_lon
 * @property numeric $token_entry_fee
 * @property int|null $wallet_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Chatroom|null $chatroom
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GroupMember> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GroupPost> $posts
 * @property-read int|null $posts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group active()
 * @method static \Database\Factories\GroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereChatroomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereLocationLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereLocationLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereMatchingEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereMaxMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereMemberCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group wherePrivacy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereTokenEntryFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereWalletId($value)
 * @mixin \Eloquent
 */
class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'privacy',
        'created_by_user_id',
        'creator_id',
        'member_count',
        'visibility',
        'is_active',
        'chatroom_id',
        'max_members',
        'token_entry_fee',
        'category',
        'tags',
        'matching_enabled',
        'location_lat',
        'location_lon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'member_count' => 'integer',
        'max_members' => 'integer',
        'matching_enabled' => 'boolean',
        'tags' => 'array',
        'location_lat' => 'double',
        'location_lon' => 'double',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function activeMembers()
    {
        // Assuming 'is_banned' column exists in group_members, or we just return all members for now if column is missing
        // But based on controller usage, it expects a filter.
        // Let's assume the column exists or will exist.
        return $this->members()->where('is_banned', false);
    }

    public function posts()
    {
        return $this->hasMany(GroupPost::class);
    }

    public function hasMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function getMemberRole($userId)
    {
        $member = $this->members()->where('user_id', $userId)->first();

        return $member ? $member->role : null;
    }

    public function isFull()
    {
        return $this->max_members > 0 && $this->member_count >= $this->max_members;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_groups');
    }
}
