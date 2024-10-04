<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RemoveTexturesJob implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    protected array $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function handle(): void
    {
        foreach ($this->files as $file) {
            if (Storage::exists($file)) {
                Storage::delete($file);
            }
        }
    }
}
