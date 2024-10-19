<?php

namespace App\Http\Controllers;

use App\Models\Sphere;
use App\Repositories\Contracts\SpheresRepositoryContract;
use App\Services\Contracts\ColorServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function __construct(protected SpheresRepositoryContract $spheresRepository)
    {
        //
    }

    /**
     * Display a listing of spheres based on the search query.
     *
     * @param Request $request The HTTP request instance.
     * @param ColorServiceContract $colorService The color service instance for color-related operations.
     * @return JsonResponse The JSON response containing the list of spheres.
     */
    public function index(Request $request, ColorServiceContract $colorService): JsonResponse
    {
        $search = $request->get('query');

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        if ($search) {

            if ($colorService->isValidHexColor($search)) {
                $color = $colorService->hexToRgb($search);
                // Fetch spheres related by the color
                $query = $this->spheresRepository->relatedByColor($color);
            } else {
                $query = $this->spheresRepository->fetchSpheres(Sphere::where('title', 'ILIKE', "%{$search}%"));
            }
        } else {
            // If no search query, fetch all spheres
            $query = $this->spheresRepository->fetchSpheres(Sphere::query());
        }

        $spheres = $query->withRating()->orderBy($sortBy, $sortOrder)->get();

        return response()->json($spheres);
    }

    /**
     * Display the specified sphere by its ID.
     *
     * @param string $id The ID of the sphere to display.
     * @return JsonResponse The JSON response containing the sphere and related spheres.
     */
    public function show(string $id): JsonResponse
    {
        $sphere = $this->spheresRepository->fetchSpheres(Sphere::where('id', $id), '*')->first();

        $sphere->load([
            'user:id,name',
        ]);

        $related = $this->spheresRepository->relatedBySphere($sphere, 3)->orderBy('distance')->get();

        return response()->json([
            'sphere' => $sphere,
            'related' => $related,
        ]);
    }
}
