<?php

namespace App\Jobs;

use App\Models\Sphere;
use App\Services\Contracts\BackgroundServiceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;

/**
 * Class PublishSphereJob
 *
 * Handles the process of publishing a sphere by generating a composite
 * image that combines the sphere's active texture with its current background.
 * This job is queued for asynchronous execution and is marked as unique
 * to prevent duplicate processing within a specified timeframe.
 */
class PublishSphereJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Sphere $sphere)
    {
        //
    }

    public int $uniqueFor = 30;

    public function uniqueId(): string
    {
        return $this->sphere->id.'publish';
    }

    /**
     * Generate the composite image of the sphere,
     * combining the active texture with the active background.
     *
     * @param  BackgroundServiceContract  $background  The service for managing background tasks.
     *
     * @throws \ImagickException If there is an error during image processing.
     * @throws \Exception If there is an error with storage or general processing.
     */
    public function handle(BackgroundServiceContract $background): void
    {
        $activeTexture = Storage::path($this->sphere->activeTexture->path);

        $currentBackground = $background->getCurrent($this->sphere->id);

        $overlayImage = new Imagick($activeTexture);

        $overlayDimensions = [
            'width' => $overlayImage->getImageWidth(),
            'height' => $overlayImage->getImageHeight(),
        ];

        $baseImage = new Imagick;
        $baseImage->newImage(
            $overlayDimensions['width'],
            $overlayDimensions['height'],
            new ImagickPixel($currentBackground)
        );

        // Composite the overlay image onto the base image.
        $baseImage->compositeImage($overlayImage, Imagick::COMPOSITE_DEFAULT, 0, 0);
        $baseImage->setImageFormat('webp');

        $path = "spheres/{$this->sphere->id}/texture.webp";

        $baseImage->writeImage(Storage::path($path));

        // Create or update the image entry in the database for the sphere.
        $this->sphere->images()
            ->where('type', 'image')
            ->firstOrCreate(
                ['type' => 'image'],
                ['path' => $path]
            );

        // Clean up resources by clearing and destroying Imagick instances.
        $overlayImage->clear();
        $overlayImage->destroy();
        $baseImage->clear();
        $baseImage->destroy();
    }
}
