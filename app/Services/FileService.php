<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService implements Contracts\FileServiceContract
{
    /**
     * Uploads a file or array as a file to the specified storage location.
     *
     * @param  UploadedFile|array  $file  The file to upload, either as an UploadedFile instance or an array of byte values.
     * @param  string  $additionalPath  An optional additional path to store the file.
     * @return string The path where the file is stored.
     */
    public function upload(UploadedFile|array $file, string $additionalPath = ''): string
    {
        $additionalPath = ! empty($additionalPath) ? $additionalPath.'/' : '';

        $fileExtension = is_array($file) ? 'webp' : $file->getClientOriginalExtension();
        $filePath = $additionalPath.Str::random(10).'.'.$fileExtension;

        // Get the file content: convert array to string if it's an array, or get the content from UploadedFile
        $content = is_array($file) ? $this->convertFileArrayToString($file) : File::get($file);

        Storage::put($filePath, $content);

        return $filePath;
    }

    /**
     * Deletes a file from the specified path and removes the directory if it's empty.
     *
     * @param  string  $filePath  The path to the file to be deleted.
     */
    public function delete(string $filePath): void
    {
        Storage::delete($filePath);

        // Split the file path into parts and exclude the last part (the file name)
        $path = collect(explode('/', $filePath));
        $path = $path->except($path->keys()->last())->implode('/');

        // Check if the directory is empty, and if so, delete the directory
        if (empty(Storage::files($path))) {
            Storage::deleteDirectory($path);
        }
    }

    /**
     * Convert an array of byte values to a string representation.
     *
     * This method takes an array where each element represents a byte value and converts
     * it into a string by interpreting each byte as a character.
     *
     * @param  array  $file  An array of integer values where each value represents a byte (0-255).
     * @return string The resulting string constructed from the byte values.
     */
    private function convertFileArrayToString(array $file): string
    {
        /*
         * Map to convert each byte value to its corresponding character using chr().
         * Then, concatenate all characters into a single string.
         */
        return collect($file)
            ->map(fn ($byte) => chr($byte))
            ->implode('');
    }
}
