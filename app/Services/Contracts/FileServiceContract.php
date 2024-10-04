<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface FileServiceContract
{
    public function upload(UploadedFile $file, string $additionalPath = ''): string;

    public function delete(string $filePath): void;
}
