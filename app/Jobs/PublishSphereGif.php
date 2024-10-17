<?php

namespace App\Jobs;

use App\Models\Sphere;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickException;
use ImagickPixel;

/**
 * Class PublishSphereGif
 * Job for publishing a GIF from a Sphere's primary image.
 *
 * This job generates a seamless animated GIF based on the sphere's texture,
 * applying a generated before mask and saving the result to storage.
 * GIFs can be used for user profile pictures or for other purposes.
 */
class PublishSphereGif implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Sphere $sphere)
    {
        //
    }

    /**
     * @throws Exception If there is an error with storage or general processing.
     * @throws ImagickException If there is an error during image processing.
     */
    public function handle(): void
    {
        try {
            $texture = new Imagick(Storage::path($this->sphere->primaryImage->path));
            $maskTexture = new Imagick(Storage::path('sphere_mask.webp'));

            /**
             * Number of frames in the GIF, this version of Imagick (6.9) cannot handle more :)
             */
            $numFrames = 30;
            $frames = new Imagick;

            $initialWidth = $texture->getImageWidth();
            $initialHeight = $texture->getImageHeight();

            // Width must be divisible by the number of frames.
            if ($initialWidth % $numFrames !== 0) {
                throw new Exception('GIF will not fit');
            }

            // Create a seamless texture by combining the texture with a mirrored part of itself.
            $seamlessTexture = new Imagick;
            $seamlessTexture->newImage($initialWidth + $initialHeight, $initialHeight, new ImagickPixel('transparent'));
            $seamlessTexture->compositeImage($texture, Imagick::COMPOSITE_OVER, 0, 0);

            // Clone and crop the right part of the texture to create a seamless texture.
            $rightPart = $texture->clone();
            $rightPart->cropImage($initialHeight, $initialHeight, 0, 0);

            // Composite the right part of the texture.
            $seamlessTexture->compositeImage($rightPart, Imagick::COMPOSITE_OVER, $initialWidth, 0);

            $offsetStep = ($initialWidth) / $numFrames;

            $xOffset = 0;

            for ($i = 0; $i <= $numFrames; $i++) {
                $frame = new Imagick;

                $frame->newImage($initialHeight, $initialHeight, new ImagickPixel('transparent'), 'webp');

                $viewportTexture = $seamlessTexture->clone();
                $viewportTexture->cropImage($initialHeight, $initialHeight, $xOffset, 0);

                $frame->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                $frame->compositeImage($viewportTexture, Imagick::COMPOSITE_OVER, 0, 0);
                $frame->compositeImage($maskTexture, Imagick::COMPOSITE_COPYOPACITY, 0, 0);
                $frame->setImageDelay(10);

                $frames->addImage($frame);

                $xOffset += $offsetStep;

                // Clear and destroy the frame and viewport texture.
                $frame->clear();
                $frame->destroy();

                $viewportTexture->clear();
                $viewportTexture->destroy();
            }

            $frames->setImageFormat('gif');

            $path = "spheres/{$this->sphere->id}/texture.gif";

            $savePath = Storage::path($path);

            $frames->writeImages($savePath, true);

            $this->sphere->images()
                ->where('type', 'gif')
                ->updateOrCreate([
                    'path' => $path,
                    'type' => 'gif',
                ]);

            $seamlessTexture->clear();
            $seamlessTexture->destroy();

            // Clear resources
            $texture->clear();
            $texture->destroy();

            $maskTexture->clear();
            $maskTexture->destroy();

            $frames->clear();
            $frames->destroy();
        } catch (Exception $e) {
            Log::error('Something went wrong here: '.$e->getMessage());
        }
    }
}
