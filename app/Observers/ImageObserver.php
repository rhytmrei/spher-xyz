<?php

namespace App\Observers;

use App\Models\Image;
use App\Services\Contracts\FileServiceContract;

class ImageObserver
{
    /**
     * Handle the Image "deleted" event.
     */
    public function deleted(Image $image): void
    {
        app(FileServiceContract::class)->delete($image->path);
    }
}
