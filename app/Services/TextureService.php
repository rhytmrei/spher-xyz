<?php

namespace App\Services;

use App\Models\Sphere;
use App\Services\Contracts\TextureServiceContract;

class TextureService implements TextureServiceContract
{
    /**
     * Removes extra textures from the sphere's texture history.
     *
     * @param  Sphere  $sphere  The sphere whose textures are to be managed.
     */
    public function removeExtraTextures(Sphere $sphere): void
    {
        $textureHistory = $sphere->textureHistory;
        $oldActiveTexture = $textureHistory->where('type', 'active')->first();

        $historyCount = $textureHistory->count();

        /**
         * If the old active texture is not the last texture in the history,
         * and the history count is greater than or equal to 7 (limit).
         */
        if ($oldActiveTexture->id < $textureHistory->last()->id || $historyCount >= 7) {

            $currentIndex = $textureHistory->search($oldActiveTexture);

            $betweenTextures = $textureHistory->slice($currentIndex + 1);

            $sliceIndex = ($currentIndex !== $historyCount - 1) ? $currentIndex - 5 : $historyCount - 6;

            $offsetTextures = $textureHistory->slice(0, max(0, $sliceIndex));

            $texturesToRemove = $betweenTextures->merge($offsetTextures);

            // Delete the identified textures while ensuring that the observers are triggered.
            $texturesToRemove->each->delete();
        }
    }
}
