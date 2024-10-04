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

    public function index(Request $request, ColorServiceContract $colorService): JsonResponse
    {
        $search = $request->get('query');

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        if ($search) {

            if ($colorService->isValidHexColor($search)) {
                $color = $colorService->hexToRgb($search);

                $query = $this->spheresRepository->relatedByColor($color);
            } else {
                $query = $this->spheresRepository->fetchSpheres(Sphere::where('title', 'ILIKE', "%{$search}%"));
            }
        } else {
            $query = $this->spheresRepository->fetchSpheres(Sphere::query());
        }

        $spheres = $query->withRating()->orderBy($sortBy, $sortOrder)->get();

        return response()->json($spheres);
    }

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
