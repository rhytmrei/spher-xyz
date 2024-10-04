<?php

namespace App\Socket;

use App\Socket\Traits\AuthTrait;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Events\MessageReceived;
use Laravel\Telescope\Telescope;

class SocketHandler
{
    use AuthTrait;

    protected array $controllers = [
        'background' => \App\Socket\Controllers\BackgroundController::class,
        'sphere' => \App\Socket\Controllers\SphereController::class,
        'texture' => \App\Socket\Controllers\TextureController::class,
    ];

    /**
     * Dispatch an event when a message is received.
     *
     * @param  MessageReceived  $event  The event containing the message data.
     */
    public function dispatch(MessageReceived $event): void
    {
        // Start recording Telescope data for incoming messages.
        Telescope::startRecording();

        $message = json_decode($event->message);

        $data = $message->data;

        // Authenticate the user based on the connection's cookie.
        $user = $this->cookieAuth($event->connection);

        if (! $user || ! property_exists($data, 'sphere_id')) {
            Log::info('Event failed to authenticate');

            return;
        }

        $sphere = auth()->user()->spheres()
            ->select('id', 'title', 'description', 'is_active')
            ->findOrFail($data->sphere_id);

        try {
            [$channelType, $controllerName, $methodName] = explode('-', $message->event);

            if (array_key_exists($controllerName, $this->controllers)) {
                $controller = app()->make($this->controllers[$controllerName]);

                if (method_exists($controller, $methodName)) {
                    $controller->{$methodName}($sphere, (array) $data);
                } else {
                    Log::error("Method '{$methodName}' doesn't exist in {$controllerName}");
                }
            } else {
                Log::error("Controller '{$controllerName}' is not defined");
            }
        } catch (Exception $e) {
            Log::error('Error in dispatch: '.$e->getMessage());
        }

        Telescope::stopRecording();
    }
}
