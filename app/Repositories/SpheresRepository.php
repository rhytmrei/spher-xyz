<?php

namespace App\Repositories;

use App\Models\Sphere;
use App\Repositories\Contracts\SpheresRepositoryContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SpheresRepository implements SpheresRepositoryContract
{
    public function relatedBySphere(Sphere $sphere, ?int $limit = null): ?Builder
    {
        if (! $sphere->texture_colors) {
            return null;
        }

        // Get the most prominent color from the sphere's texture colors
        $rgb = collect($sphere->texture_colors)
            ->sortByDesc('percentage')
            ->pluck('color')
            ->first();

        return $this->relatedByColor($rgb, $limit);
    }

    public function relatedByColor(array $rgb, ?int $limit = null): Builder
    {
        // Prepare a subquery to get colors and their percentages from all spheres
        $subquery = Sphere::select(
            'id AS original_id',
            DB::raw('color_data->\'color\' AS color'),
            DB::raw('(color_data->>\'percentage\')::numeric AS percentage')
        )
            ->crossJoin(DB::raw('jsonb_array_elements(texture_colors) AS color_data'));

        $results = $this->fetchSpheres(Sphere::where('is_active', true))
            ->joinSub($subquery, 'mpc', function ($join) {
                $join->on('spheres.id', '=', 'mpc.original_id');
            })
            ->whereIn('mpc.percentage', function ($query) {
                // Find the maximum percentage for each sphere in the subquery
                $query->select(DB::raw('MAX((color_data->>\'percentage\')::numeric)'))
                    ->from('spheres')
                    ->crossJoin(DB::raw('jsonb_array_elements(texture_colors) AS color_data'))
                    ->groupBy('spheres.id');
            })
            // Calculate the Euclidean distance between the colors
            ->selectRaw('
                sqrt(
                    power((mpc.color->>0)::int - ?, 2) +
                    power((mpc.color->>1)::int - ?, 2) +
                    power((mpc.color->>2)::int - ?, 2)
                )::float AS distance
            ', $rgb)
            ->limit($limit);

        return $results;
    }

    public function fetchSpheres(
        Builder $query,
        array|string $rows = ['id', 'is_active', 'title', 'user_id', 'created_at'],
        bool $activeOnly = true
    ): Builder {
        $result = $query->with(['primaryImage', 'reactions'])
            ->select($rows)
            ->withReactionCounts();

        if ($activeOnly) {
            $result = $result->where('is_active', true);
        }

        // If a user is authenticated, include their reactions
        if ($userId = auth()->id()) {
            $result = $result->withUserReactions($userId);
        }

        return $result;
    }
}
