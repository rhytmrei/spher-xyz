<?php

namespace App\Providers;

use App\Repositories\Contracts\SpheresRepositoryContract;
use App\Repositories\SpheresRepository;
use App\Services\BackgroundService;
use App\Services\ColorService;
use App\Services\Contracts\BackgroundServiceContract;
use App\Services\Contracts\ColorServiceContract;
use App\Services\Contracts\FileServiceContract;
use App\Services\Contracts\TextureServiceContract;
use App\Services\FileService;
use App\Services\TextureService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        BackgroundServiceContract::class => BackgroundService::class,
        TextureServiceContract::class => TextureService::class,
        FileServiceContract::class => FileService::class,
        ColorServiceContract::class => ColorService::class,

        SpheresRepositoryContract::class => SpheresRepository::class,
    ];

    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void {}
}
