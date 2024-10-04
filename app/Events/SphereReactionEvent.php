<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Bus\Dispatchable;

class SphereReactionEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public string $sphereId, public array $message)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        return new Channel("sphere.{$this->sphereId}.rating");
    }

    public function broadcastAs(): string
    {
        return 'update';
    }
}
