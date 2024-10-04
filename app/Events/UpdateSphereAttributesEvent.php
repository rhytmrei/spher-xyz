<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateSphereAttributesEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public string $sphereId, public string $eventName, public array $message)
    {
        //
    }

    public function broadcastOn(): Channel
    {
        return new Channel("sphere.{$this->sphereId}.listen");
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }
}
