<?php

namespace App\Socket\Controllers;

use App\Events\UpdateSphereAttributesEvent;
use App\Jobs\PublishSphereColorsJob;
use App\Jobs\PublishSphereGif;
use App\Jobs\PublishSphereJob;
use App\Models\Sphere;
use App\Services\Contracts\BackgroundServiceContract;
use App\Services\Contracts\TextureServiceContract;

class SphereController
{
    public function __construct(
        protected TextureServiceContract $textureService,
        protected BackgroundServiceContract $backgroundService,
    ) {
        //
    }

    public function get(Sphere $sphere): void
    {
        $sphere->load(['activeTexture', 'textureHistory']);

        $sphere->textureHistory->transform(function ($texture) {
            return [
                'id' => $texture->id,
                'url' => $texture->url,
            ];
        });

        $sphere->makeHidden('image_src');

        $result = [
            'sphere' => $sphere,
            'background' => [
                'current' => $this->backgroundService->getCurrent($sphere->id),
                'history' => $this->backgroundService->getHistory($sphere->id),
            ],
        ];

        broadcast(new UpdateSphereAttributesEvent($sphere->id, 'update-all', $result));
    }

    public function publish(Sphere $sphere, array $data = []): void
    {
        PublishSphereJob::dispatch($sphere);

        PublishSphereColorsJob::dispatch($sphere)->delay(now()->addSeconds(2));

        PublishSphereGif::dispatch($sphere)->delay(now()->addSeconds(4));
    }
}
