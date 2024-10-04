<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SpheresRepositoryContract
{
    public function relatedByColor(array $rgb, int $limit): Builder;

    public function fetchSpheres(Builder $query, array $rows, bool $activeOnly): Builder;
}
