<?php

namespace App\Events;

use App\Models\GroupMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class GroupMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(GroupMessage $message)
    {
        $this->message = $message;
        Log::info(['group' => $this->message->group_id]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('group.' . $this->message->group_id);
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'message' => $this->message->message,
                'type' => $this->message->type,
                'reply_to' => $this->message->reply_to,
                'created_at' => $this->message->created_at,
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                    'avatar' => $this->message->user->avatar,
                ],
                'replyTo' => $this->message->replyTo ? [
                    'id' => $this->message->replyTo->id,
                    'message' => $this->message->replyTo->message,
                    'user' => [
                        'name' => $this->message->replyTo->user->name,
                    ],
                ] : null,
            ]
        ];
    }
}

