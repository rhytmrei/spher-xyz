<?php

namespace App\Jobs;

use App\Models\Sphere;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Class PublishSphereColorsJob
 *
 * Processes the colors of a sphere's active texture and saves the color
 * percentages to the sphere model.
 */
class PublishSphereColorsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Sphere $sphere)
    {
        //
    }

    public int $uniqueFor = 30;

    public function uniqueId(): string
    {
        return $this->sphere->id.'colors';
    }

    /**
     * Execute the job to process the sphere's colors from its active texture.
     *
     * @throws \ImagickException If there is an error during image processing.
     * @throws \Exception If there is a general error during job execution.
     */
    public function handle(): void
    {
        try {

            $currentTexture = Storage::path($this->sphere->activeTexture->path);

            $overlayImage = new Imagick($currentTexture);

            $width = $overlayImage->getImageWidth();
            $height = $overlayImage->getImageHeight();

            // Export image pixels in RGBA format.
            $pixels = $overlayImage->exportImagePixels(0, 0, $width, $height, 'RGBA', Imagick::PIXEL_CHAR);

            $pixelCount = count($pixels);

            $pixel_x = 0;
            $pixel_y = 0;
            $draw = new ImagickDraw;

            // Iterate through pixels to draw points for non-transparent pixels.
            for ($i = 0; $i < $pixelCount; $i += 4) {

                if ($pixels[$i + 3] === 0) {
                    continue;
                }

                $c = sprintf(
                    '#%02x%02x%02x',
                    $pixels[$i],
                    $pixels[$i + 1],
                    $pixels[$i + 2]
                );

                $draw->setFillColor(new ImagickPixel($c));

                $draw->point($pixel_x, $pixel_y);

                if ($pixel_y === $height) {
                    $pixel_x++;
                    $pixel_y = 0;
                }

                $pixel_y++;

            }

            // Create a new image for the drawn points.
            $image = new Imagick;
            $image->newImage($pixel_x, $height, new ImagickPixel('white'), 'webp');
            $image->drawImage($draw);
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);
            $image->quantizeImage(5, Imagick::COLORSPACE_RGB, 5, true, false);
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);

            $colors = $image->getImageHistogram();

            $colorCounts = collect($colors)->map(function ($color) {

                $rgbColor = collect([
                    Imagick::COLOR_RED,
                    Imagick::COLOR_GREEN,
                    Imagick::COLOR_BLUE,
                ])
                    ->map(fn ($channel) => round($color->getColorValue($channel) * 255))
                    ->toArray();

                return [
                    'color' => $rgbColor,
                    'count' => $color->getColorCount(),
                ];
            })->toArray();

            $totalCount = collect($colorCounts)->sum('count');

            $colorPercentages = collect($colorCounts)
                ->map(function ($colorCount) use ($totalCount) {
                    $percentage = ($colorCount['count'] / $totalCount) * 100;

                    return [
                        'color' => $colorCount['color'],
                        'percentage' => round($percentage, 2),
                    ];
                })
                ->filter(fn ($item) => $item['percentage'] > 1)
                ->values()
                ->toArray();

            // Save the texture colors to the given sphere model.
            $this->sphere->texture_colors = $colorPercentages;
            $this->sphere->save();

        } catch (Exception $e) {
            Log::info($e->getMessage());
        }

    }
}
