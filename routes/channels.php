<?php

use App\Models\Group;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('presence.chat', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    $group = Group::find($groupId);
    Log::info(['group' => $group]);
    return true;
    return $group && $group->isMember($user);
});
