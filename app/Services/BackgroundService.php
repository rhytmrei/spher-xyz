<?php

namespace App\Services;

use App\Services\Contracts\BackgroundServiceContract;
use Illuminate\Support\Facades\Redis;

class BackgroundService implements BackgroundServiceContract
{
    public function currentKey(string $sphere_id): string
    {
        return "sphere:{$sphere_id}:background_active";
    }

    public function historyKey(string $sphere_id): string
    {
        return "sphere:{$sphere_id}:background_history";
    }

    public function setCurrent(string $sphere_id, int|string $value): bool
    {
        if (collect($this->getHistory($sphere_id))->contains($value)) {
            $activeKey = $this->currentKey($sphere_id);

            return Redis::set($activeKey, $value);
        }

        return false;
    }

    public function getCurrent(string $sphere_id): string
    {
        return Redis::get($this->currentKey($sphere_id));
    }

    public function storeToHistory(string $sphere_id, string $value): bool
    {
        $historyKey = $this->historyKey($sphere_id);

        Redis::rpush($historyKey, $value);

        Redis::ltrim($historyKey, -7, -1);

        return $this->setCurrent($sphere_id, $value);
    }

    public function getHistory(string $sphere_id): array
    {
        return Redis::lrange($this->historyKey($sphere_id), 0, -1);
    }
}
