<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\SpheresRepositoryContract;
use Illuminate\Http\JsonResponse;

class ShowController extends Controller
{
    public function __invoke(string $name, SpheresRepositoryContract $spheresRepository): JsonResponse
    {
        $user = User::where('name', $name)
            ->select('id', 'name')
            ->with(['spheres' => function ($query) use ($spheresRepository) {
                return $spheresRepository->fetchSpheres($query->where('is_active', true)->getQuery());
            }])
            ->firstOrFail();

        if (! $user) {
            abort(404);
        }

        $user->total_likes = $user->spheres->sum('likes_count') ?? 0;

        return response()->json($user);
    }
}
