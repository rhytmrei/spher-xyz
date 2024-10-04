<?php

namespace App\Listeners;

use App\Socket\SocketHandler;
use Illuminate\Support\Str;
use Laravel\Reverb\Events\MessageReceived;

class BroadcastMessage
{
    public function __construct()
    {
        //
    }

    /**
     * Handle the incoming message received event from reverb.
     *
     * @param  MessageReceived  $event  The event containing the message data.
     */
    public function handle(MessageReceived $event): void
    {
        $socketEvent = (json_decode($event->message))->event;

        // ignore pusher ping pong events
        if (Str::startsWith($socketEvent, 'pusher')) {
            return;
        }

        // Dispatch the event using custom handler.
        app(SocketHandler::class)->dispatch($event);
    }
}
