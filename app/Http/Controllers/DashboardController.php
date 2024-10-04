<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\EditRequest;
use App\Models\Sphere;
use App\Repositories\Contracts\SpheresRepositoryContract;
use App\Services\Contracts\BackgroundServiceContract;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(SpheresRepositoryContract $repository): JsonResponse
    {
        $spheres = $repository->fetchSpheres(
            auth()->user()->spheres()->getQuery(),
            activeOnly: false
        )->orderByDesc('created_at')->get();

        $spheres->load('user:id,name');

        return response()->json(['result' => $spheres]);
    }

    public function create(BackgroundServiceContract $background): JsonResponse
    {
        $sphere = auth()->user()->spheres()->create([
            'title' => '',
            'description' => '',
            'is_active' => false,
        ]);

        $sphere->images()->create([
            'path' => Sphere::defaultImage(),
            'type' => 'active',
        ]);

        $background->storeToHistory($sphere->id, '#000000');

        return response()->json(['id' => $sphere->id]);
    }

    public function edit(EditRequest $request): JsonResponse
    {
        $sphere = auth()->user()->spheres()->findOrFail($request->get('id'));

        $sphere->update([$request->get('key') => $request->get('value')]);

        return response()->json([
            'result' => 'success',
            'affected' => [
                $request->get('key') => $request->get('value'),
            ],
        ]);
    }
}
