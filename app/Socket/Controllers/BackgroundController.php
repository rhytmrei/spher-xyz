<?php

namespace App\Socket\Controllers;

use App\Events\UpdateSphereAttributesEvent;
use App\Models\Sphere;
use App\Services\Contracts\BackgroundServiceContract;
use Illuminate\Support\Facades\Validator;

class BackgroundController
{
    public function __construct(protected BackgroundServiceContract $backgroundService) {}

    protected function validator(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'color' => ['required', 'regex:/^#[a-f0-9]{6}$/'],
        ]);
    }

    public function setCurrent(Sphere $sphere, array $data): void
    {
        $validator = $this->validator($data);

        if ($validator->fails()) {
            return;
        }

        $this->backgroundService->setCurrent($sphere->id, $data['color']);
    }

    public function store(Sphere $sphere, array $data): void
    {
        $validator = $this->validator($data);

        if ($validator->fails()) {
            return;
        }

        $color = $data['color'];

        if ($this->backgroundService->storeToHistory($sphere->id, $color)) {
            $this->backgroundService->setCurrent($sphere->id, $color);
        }

        $this->broadcastUpdate($sphere, $color);
    }

    protected function broadcastUpdate(Sphere $sphere, string $color): void
    {
        $response = [
            'sphere' => $sphere,
            'background' => [
                'current' => $color,
                'history' => $this->backgroundService->getHistory($sphere->id),
            ],
        ];

        broadcast(new UpdateSphereAttributesEvent($sphere->id, 'update-background', $response));
    }
}
