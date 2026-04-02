<?php

namespace App\Services;

use App\Models\Friend;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContentVisibilityService
{
    public function canViewJournal(?User $viewer, Journal $journal): bool
    {
        if ($viewer && $viewer->id === $journal->user_id) {
            return true;
        }

        return match ($journal->visibility) {
            Journal::VISIBILITY_PUBLIC => true,
            Journal::VISIBILITY_FRIENDS => $viewer ? $this->areFriends($viewer, $journal->user) : false,
            Journal::VISIBILITY_CIRCLE => $viewer ? $this->sharesCircle($viewer, $journal) : false,
            default => false,
        };
    }

    public function getVisibleJournalsForProfile(User $owner, ?User $viewer, int $limit = 3): Collection
    {
        $query = $owner->journals()
            ->with('circleGroup:id,name,privacy')
            ->latest();

        if ($viewer && $viewer->id === $owner->id) {
            return $query->limit($limit)->get();
        }

        $isFriend = $viewer ? $this->areFriends($viewer, $owner) : false;

        $query->where(function (Builder $builder) use ($viewer, $isFriend) {
            $builder->where('visibility', Journal::VISIBILITY_PUBLIC);

            if ($isFriend) {
                $builder->orWhere('visibility', Journal::VISIBILITY_FRIENDS);
            }

            if ($viewer) {
                $builder->orWhere(function (Builder $circleQuery) use ($viewer) {
                    $circleQuery
                        ->where('visibility', Journal::VISIBILITY_CIRCLE)
                        ->whereNotNull('circle_group_id')
                        ->whereExists(function ($subquery) use ($viewer) {
                            $subquery->selectRaw('1')
                                ->from('group_members')
                                ->whereColumn('group_members.group_id', 'journals.circle_group_id')
                                ->where('group_members.user_id', $viewer->id)
                                ->where('group_members.is_banned', false)
                                ->where('group_members.is_active', true);
                        });
                });
            }
        });

        return $query->limit($limit)->get();
    }

    public function getAvailableCircles(User $user): Collection
    {
        return $user->groups()
            ->select('groups.id', 'groups.name', 'groups.privacy')
            ->wherePivot('is_banned', false)
            ->wherePivot('is_active', true)
            ->orderBy('groups.name')
            ->get();
    }

    public function ensureUserCanUseCircle(User $user, ?int $groupId): ?Group
    {
        if (! $groupId) {
            return null;
        }

        return $user->groups()
            ->where('groups.id', $groupId)
            ->wherePivot('is_banned', false)
            ->wherePivot('is_active', true)
            ->first();
    }

    public function areFriends(User|int $firstUser, User|int $secondUser): bool
    {
        $firstUserId = $firstUser instanceof User ? $firstUser->id : $firstUser;
        $secondUserId = $secondUser instanceof User ? $secondUser->id : $secondUser;

        return Friend::query()
            ->where('status', 'accepted')
            ->where(function (Builder $query) use ($firstUserId, $secondUserId) {
                $query->where(function (Builder $forward) use ($firstUserId, $secondUserId) {
                    $forward->where('user_id', $firstUserId)
                        ->where('friend_id', $secondUserId);
                })->orWhere(function (Builder $reverse) use ($firstUserId, $secondUserId) {
                    $reverse->where('user_id', $secondUserId)
                        ->where('friend_id', $firstUserId);
                });
            })
            ->exists();
    }

    private function sharesCircle(User $viewer, Journal $journal): bool
    {
        if (! $journal->circle_group_id) {
            return false;
        }

        return GroupMember::query()
            ->where('group_id', $journal->circle_group_id)
            ->where('user_id', $viewer->id)
            ->where('is_banned', false)
            ->where('is_active', true)
            ->exists();
    }
}
