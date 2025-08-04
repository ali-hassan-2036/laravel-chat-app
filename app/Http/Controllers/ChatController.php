<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessageDelivered;
use App\Events\MessageRead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function show(User $user)
    {
        $messages = Message::where(function ($q) use ($user) {
            $q->where('sender_id', auth()->id())
                ->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user) {
            $q->where('sender_id', $user->id)
                ->where('receiver_id', auth()->id());
        })->orderBy('created_at')->get();

        // Mark all messages sent to the current user by the other user as delivered
        $undelivered = Message::where('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('delivered_at')
            ->get();
        foreach ($undelivered as $msg) {
            $msg->delivered_at = now();
            $msg->save();
            broadcast(new MessageDelivered($msg));
        }

        return Inertia::render('Users/ChatContainer', [
            'user' => $user,
            'messages' => $messages,
            'authUserId' => auth()->id(),
        ]);
    }

     // Store new message
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        broadcast(new MessageSent($message));
        // Return the message object in the Inertia response
        return redirect()->back()->with([
            'message' => $message
        ]);
    }

    // Mark messages as read
    public function markAsRead(Request $request, User $user)
    {
        Log::info('markAsRead');
        $messages = Message::where('sender_id', $user->id)
        ->where('receiver_id', auth()->id())
        ->where('is_read', false)
        ->get();

        foreach ($messages as $message) {
            $message->update([
                'is_read' => true,
                'delivered_at' => $message->delivered_at ?? now()
            ]);

            broadcast(new MessageRead($message))->toOthers();
        }
        
        return redirect()->back();
    }

    // Mark messages as delivered
    public function markAsDelivered(Request $request, User $user)
    {
        Log::info('markAsDelivered');

        $messages = Message::where('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('delivered_at')
            ->get();

        foreach ($messages as $message) {
            $message->update(['delivered_at' => now()]);
            broadcast(new MessageDelivered($message))->toOthers();
        }

        return redirect()->back();
    }

}
