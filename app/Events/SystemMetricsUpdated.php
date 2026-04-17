<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly array $metrics)
    {
    }

    /**
     * Broadcast on the public metrics channel.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('metrics'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'metrics.updated';
    }

    public function broadcastWith(): array
    {
        return $this->metrics;
    }
}
