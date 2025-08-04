<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Events\UserJoinedGroup;
use App\Events\UserLeftGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupMemberController extends Controller
{
    public function store(Request $request, Group $group)
    {
        if (!$group->isModerator(Auth::user())) {
            abort(403, 'Only moderators can add members.');
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $addedUsers = [];
        foreach ($request->user_ids as $userId) {
            $user = User::find($userId);
            
            if (!$group->isMember($user) && $group->getMemberCount() < $group->max_members) {
                $group->members()->attach($userId, [
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
                
                $addedUsers[] = $user;
                broadcast(new UserJoinedGroup($user, $group));
            }
        }

        return back()->with('success', count($addedUsers) . ' members added successfully!');
    }

    public function update(Request $request, Group $group, User $user)
    {
        if (!$group->isAdmin(Auth::user())) {
            abort(403, 'Only admins can change member roles.');
        }

        if (!$group->isMember($user)) {
            abort(404, 'User is not a member of this group.');
        }

        $request->validate([
            'role' => 'required|in:admin,moderator,member',
        ]);

        $group->members()->updateExistingPivot($user->id, [
            'role' => $request->role,
        ]);

        return back()->with('success', 'Member role updated successfully!');
    }

    public function destroy(Group $group, User $user)
    {
        if (!$group->isModerator(Auth::user()) && Auth::id() !== $user->id) {
            abort(403, 'Only moderators can remove members.');
        }

        if ($group->created_by === $user->id) {
            abort(403, 'Cannot remove the group creator.');
        }

        if (!$group->isMember($user)) {
            abort(404, 'User is not a member of this group.');
        }

        $group->members()->detach($user->id);
        broadcast(new UserLeftGroup($user, $group));

        return back()->with('success', 'Member removed successfully!');
    }
}
