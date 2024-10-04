<?php

namespace App\Services\Contracts;

interface ColorServiceContract
{
    public function isValidHexColor($color): bool;

    public function hexToRgb($color): array;
}
