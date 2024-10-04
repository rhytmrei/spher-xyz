<?php

namespace App\Services\Contracts;

use App\Models\Sphere;

interface TextureServiceContract
{
    public function removeExtraTextures(Sphere $sphere): void;
}
