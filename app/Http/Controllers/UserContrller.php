<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use function Termwind\render;

class UserContrller extends Controller
{
    public function index()
    {
        $authUser = Auth::user();
        $authUserId = $authUser->id;
        
        $users = User::where('id', '!=', $authUserId)
            ->orderBy('id', 'desc')
            ->get();

        // Get the last message between the auth user and each chat user
        $usersWithLastMessage = $users->map(function ($user) use ($authUserId) {
            $lastMessage = \App\Models\Message::where(function ($query) use ($authUserId, $user) {
                    $query->where(function ($q) use ($authUserId, $user) {
                        $q->where('sender_id', $authUserId)
                          ->where('receiver_id', $user->id);
                    })->orWhere(function ($q) use ($authUserId, $user) {
                        $q->where('sender_id', $user->id)
                          ->where('receiver_id', $authUserId);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'last_message' => $lastMessage ? $lastMessage->message : null,
                'last_message_at' => $lastMessage ? $lastMessage->created_at : null,
            ];
        });

        // Get the groups the auth user belongs to, with last message and last message time
        $groups = $authUser->groups()
            ->withCount('members')
            ->with(['messages' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }, 'messages.user:id,name'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($group) use ($authUser) {
                // Get the latest message for the group
                $latestMessage = $group->messages->first();
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'image' => $group->image,
                    'members_count' => $group->members_count,
                    'is_admin' => $group->isAdmin($authUser),
                    'is_moderator' => $group->isModerator($authUser),
                    'latest_message' => $latestMessage ? [
                        'message' => $latestMessage->message,
                        'created_at' => $latestMessage->created_at,
                        'user' => $latestMessage->user ? $latestMessage->user->name : null,
                    ] : null,
                ];
            });

        return Inertia::render('Users/Index', [
            'users' => $usersWithLastMessage,
            'groups' => $groups,
        ]);
    }
}
