<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
*/

Broadcast::channel('groups.{groupId}', function ($user, int $groupId) {
    $group = Group::find($groupId);
    if (!$group) {
        return false;
    }
    return $group->hasMember($user->id);
});
