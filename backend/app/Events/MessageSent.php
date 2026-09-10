<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly array $recipientIds
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel(
                'conversations.'
                .$this->message->conversation_id
            ),
        ];

        foreach (
            $this->recipientIds as $recipientId
        ) {
            $channels[] =
                new PrivateChannel(
                    'inbox.'
                    .$recipientId
                );
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,

            'conversation_id' => $this->message->conversation_id,

            'sender_id' => $this->message->sender_id,
        ];
    }
}
