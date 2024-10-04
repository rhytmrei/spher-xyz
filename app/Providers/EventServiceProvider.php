<?php

namespace App\Providers;

use App\Listeners\BroadcastMessage;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Reverb\Events\MessageReceived;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageReceived::class => [
            BroadcastMessage::class,
        ],
    ];

    public function boot()
    {
        parent::boot();
    }
}
