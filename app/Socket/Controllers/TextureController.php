<?php

namespace App\Socket\Controllers;

use App\Events\UpdateSphereAttributesEvent;
use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Sphere;
use App\Services\Contracts\FileServiceContract;
use App\Services\Contracts\TextureServiceContract;

class TextureController extends Controller
{
    public function __construct(protected TextureServiceContract $textureService, protected FileServiceContract $fileService) {}

    /**
     * Store a new texture for the given sphere and manage the texture history.
     *
     * @param  Sphere  $sphere  The sphere to which the texture belongs.
     * @param  array  $data  The data containing the texture file.
     */
    public function store(Sphere $sphere, array $data): void
    {
        $path = $this->fileService->upload($data['file'], "spheres/{$sphere->id}/history");

        // Remove any extra textures from the sphere's history to maintain a limit of 7.
        $this->textureService->removeExtraTextures($sphere);

        // Update the active texture's type to 'texture', it is no longer the active texture.
        $sphere->activeTexture()->update(['type' => 'texture']);

        $newActiveTexture = $sphere->images()->create([
            'path' => $path,
            'type' => 'active',
        ]);

        // Load the updated texture history for the sphere.
        $sphere->load(['textureHistory']);

        $response = [
            'active' => $newActiveTexture->only(['id', 'url']),
            'history' => $sphere->only(['textureHistory']),
        ];

        // Broadcast an event to update the sphere's texture.
        broadcast(new UpdateSphereAttributesEvent($sphere->id, 'update-texture', $response));
    }

    /**
     * Set the current active texture for the given sphere.
     *
     * @param  Sphere  $sphere  The sphere to which the texture belongs.
     * @param  array  $data  The data containing the index of the image to be set as active.
     */
    public function setCurrent(Sphere $sphere, array $data): void
    {
        $sphere->load('activeTexture');
        $image = $sphere->images()->where('id', $data['index'])->first();

        Image::withoutEvents(function () use ($sphere) {
            $sphere->activeTexture->update(['type' => 'texture']);
        });

        if ($image) {
            $image->type = 'active';
            $image->saveQuietly();
        }
    }
}
