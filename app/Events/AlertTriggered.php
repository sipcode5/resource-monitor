<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertTriggered implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $type,
        public readonly string $message,
        public readonly string $severity,
        public readonly array  $context = []
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.triggered';
    }

    public function broadcastWith(): array
    {
        return [
            'type'      => $this->type,
            'message'   => $this->message,
            'severity'  => $this->severity,
            'context'   => $this->context,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
