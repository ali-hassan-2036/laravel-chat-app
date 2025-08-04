<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Auth::user()->groups()
            ->with(['latestMessage.user', 'members'])
            ->withCount('members')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'image' => $group->image,
                    'members_count' => $group->members_count,
                    'latest_message' => $group->latestMessage ? [
                        'message' => $group->latestMessage->message,
                        'created_at' => $group->latestMessage->created_at,
                        'user' => $group->latestMessage->user->name,
                    ] : null,
                    'is_admin' => $group->isAdmin(Auth::user()),
                    'is_moderator' => $group->isModerator(Auth::user()),
                ];
            });

        return Inertia::render('Groups/Index', [
            'groups' => $groups
        ]);
    }

    public function show(Group $group)
    {
        if (!$group->isMember(Auth::user())) {
            abort(403, 'You are not a member of this group.');
        }

        $messages = $group->messages()
            ->with(['user', 'replyTo.user'])
            ->paginate(50);

        $members = $group->members()
            ->withPivot(['role', 'joined_at'])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'role' => $member->pivot->role,
                    'joined_at' => $member->pivot->joined_at,
                ];
            });

        $users = User::where('id', '!=', auth()->id())->orderBy('created_at', 'desc')->get();

        return Inertia::render('Users/GroupChat', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'image' => $group->image,
                'created_by' => $group->created_by,
                'is_private' => $group->is_private,
                'max_members' => $group->max_members,
                'is_admin' => $group->isAdmin(Auth::user()),
                'is_moderator' => $group->isModerator(Auth::user()),
            ],
            'messages' => $messages,
            'members' => $members,
            'users'  => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
            'is_private' => 'boolean',
            'max_members' => 'integer|min:2|max:1000',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('group-images', 'public');
        }

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
            'created_by' => Auth::id(),
            'is_private' => $request->is_private ?? false,
            'max_members' => $request->max_members ?? 100,
        ]);

        // Add creator as admin
        $group->members()->attach(Auth::id(), [
            'role' => 'admin',
            'joined_at' => now(),
        ]);


        return redirect()->back()
            ->with('success', 'Group created successfully!');
        // return redirect()->route('groups.show', $group)
        //     ->with('success', 'Group created successfully!');
    }

    public function update(Request $request, Group $group)
    {
        if (!$group->isAdmin(Auth::user())) {
            abort(403, 'Only admins can update group details.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
            'is_private' => 'boolean',
            'max_members' => 'integer|min:2|max:1000',
        ]);

        $imagePath = $group->image;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('group-images', 'public');
        }

        $group->update([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
            'is_private' => $request->is_private,
            'max_members' => $request->max_members,
        ]);

        return back()->with('success', 'Group updated successfully!');
    }

    public function destroy(Group $group)
    {
        if (!$group->isAdmin(Auth::user()) && $group->created_by !== Auth::id()) {
            abort(403, 'Only the group creator can delete the group.');
        }

        if ($group->image) {
            Storage::disk('public')->delete($group->image);
        }

        $group->delete();

        return redirect()->route('groups.index')
            ->with('success', 'Group deleted successfully!');
    }

    public function join(Group $group)
    {
        if ($group->isMember(Auth::user())) {
            return back()->with('error', 'You are already a member of this group.');
        }

        if ($group->getMemberCount() >= $group->max_members) {
            return back()->with('error', 'Group has reached maximum member limit.');
        }

        $group->members()->attach(Auth::id(), [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Joined group successfully!');
    }

    public function leave(Group $group)
    {
        if (!$group->isMember(Auth::user())) {
            return back()->with('error', 'You are not a member of this group.');
        }

        if ($group->created_by === Auth::id()) {
            return back()->with('error', 'Group creator cannot leave the group. Delete the group instead.');
        }

        $group->members()->detach(Auth::id());

        return redirect()->route('groups.index')
            ->with('success', 'Left group successfully!');
    }
}

