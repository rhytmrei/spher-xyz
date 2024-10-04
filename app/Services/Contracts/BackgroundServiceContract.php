<?php

namespace App\Services\Contracts;

interface BackgroundServiceContract
{
    public function currentKey(string $sphere_id): string;

    public function historyKey(string $sphere_id): string;

    public function setCurrent(string $sphere_id, int|string $value): bool;

    public function getCurrent(string $sphere_id): string;

    public function storeToHistory(string $sphere_id, string $value): bool;

    public function getHistory(string $sphere_id): array;
}
