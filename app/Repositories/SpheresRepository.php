<?php

namespace App\Repositories;

use App\Models\Sphere;
use App\Repositories\Contracts\SpheresRepositoryContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SpheresRepository implements SpheresRepositoryContract
{
    /**
     * Retrieve spheres related to a given sphere based on its texture colors.
     *
     * This method finds the most prominent color from the sphere's texture colors and returns spheres
     * that are similar in color using the `relatedByColor` method.
     *
     * @param Sphere $sphere The sphere instance for which to find related spheres.
     * @param int|null $limit Optional. Limit the number of results. Default is null (no limit).
     *
     * @return Builder|null A query builder instance for related spheres, or null if the sphere has no texture colors.
     */
    public function relatedBySphere(Sphere $sphere, ?int $limit = null): ?Builder
    {
        if (!$sphere->texture_colors) {
            return null;
        }

        // Get the most prominent color from the sphere's texture colors
        $rgb = collect($sphere->texture_colors)
            ->sortByDesc('percentage')
            ->pluck('color')
            ->first();

        return $this->relatedByColor($rgb, $limit);
    }

    /**
     * Retrieve spheres related by a given RGB color value.
     *
     * This method finds spheres with similar color profiles by calculating the Euclidean distance between
     * the given RGB color and the color data of other spheres. A distance threshold is used to determine
     * similarity.
     *
     * @param array $rgb The RGB color array [R, G, B] to search for similar colors.
     * @param int|null $limit Optional. Limit the number of results. Default is null (no limit).
     *
     * @return Builder A query builder instance for related spheres based on color similarity.
     */
    public function relatedByColor(array $rgb, ?int $limit = null): Builder
    {
        $distanceThreshold = 130;

        $distanceQuery = '
            sqrt(
                power((mpc.color->>0)::int - ?, 2) +
                power((mpc.color->>1)::int - ?, 2) +
                power((mpc.color->>2)::int - ?, 2)
            )::float
        ';

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
            ->selectRaw("{$distanceQuery} AS distance", $rgb)
            ->whereRaw("{$distanceQuery} < ?", [...$rgb, $distanceThreshold])
            ->limit($limit);

        return $results;
    }

    /**
     * Fetch a set of spheres based on the given query.
     *
     * This method prepares a base query for fetching spheres, including optional reaction data and
     * relations like primary image and reaction counts. It also includes the authenticated user's
     * reaction data, if available.
     *
     * @param Builder $query The base query builder for retrieving spheres.
     * @param array|string $rows Optional. The columns to select from the spheres table.
     * @param bool $activeOnly Optional. Whether to restrict the query to active spheres only. Default is true.
     *
     * @return Builder The prepared query builder for fetching spheres.
     */
    public function fetchSpheres(
        Builder      $query,
        array|string $rows = ['id', 'is_active', 'title', 'user_id', 'created_at'],
        bool         $activeOnly = true
    ): Builder
    {
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
