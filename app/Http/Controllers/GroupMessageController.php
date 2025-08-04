<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMessage;
use App\Events\GroupMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupMessageController extends Controller
{
    public function store(Request $request, Group $group)
    {
        if (!$group->isMember(Auth::user())) {
            abort(403, 'You are not a member of this group.');
        }

        $request->validate([
            'message' => 'required|string|max:1000',
            'reply_to' => 'nullable|exists:group_messages,id',
        ]);

        $message = GroupMessage::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'reply_to' => $request->reply_to,
        ]);

        // Load relationships
        $message->load(['user', 'replyTo.user']);

        // Format the message data
        $messageData = [
            'id' => $message->id,
            'message' => $message->message,
            'type' => $message->type ?? 'text', // Add default type if not set
            'reply_to' => $message->reply_to,
            'created_at' => $message->created_at,
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'avatar' => $message->user->avatar,
            ],
            'replyTo' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'message' => $message->replyTo->message,
                'user' => [
                    'name' => $message->replyTo->user->name,
                ],
            ] : null,
        ];

        // Log for debugging
        Log::info('Broadcasting message for group: ' . $group->id, ['message' => $messageData]);

        // Broadcast to group members
        broadcast(new GroupMessageSent($message))->toOthers();

        return response()->json([
            'message' => $messageData
        ]);
    }

    public function destroy(GroupMessage $message)
    {
        $group = $message->group;
        
        if ($message->user_id !== Auth::id() && !$group->isModerator(Auth::user())) {
            abort(403, 'You can only delete your own messages or be a moderator.');
        }

        $message->delete();

        return response()->json(['success' => true]);
    }
}