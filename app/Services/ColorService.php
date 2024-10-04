<?php

namespace App\Services;

use App\Services\Contracts\ColorServiceContract;

class ColorService implements ColorServiceContract
{
    public function isValidHexColor($color): bool
    {
        return preg_match('/^#([A-Fa-f0-9]{6})$/', $color);
    }

    public function hexToRgb($color): array
    {
        $color = ltrim($color, '#');

        return [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ];
    }
}
