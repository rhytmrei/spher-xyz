<?php

namespace App\Http\Controllers;

use App\Models\Sphere;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index(int $limit = 40): JsonResponse
    {
        $spheres = Cache::remember("spheres.home.{$limit}", now()->addMinutes(5), function () use ($limit) {
            $spheres = Sphere::orderBy('created_at', 'desc')->with('primaryImage')->limit($limit)->get('id')->pluck('image_src');

            $defaultImageUrl = Storage::url(Sphere::defaultImage());

            return $spheres->pad($limit, $defaultImageUrl);
        });

        $statistics = Cache::remember('statistics.home', now()->addDay(), function () {
            return [
                'spheres' => Sphere::where('created_at', '>=', Carbon::now()->subMonth())->count(),
                'users' => User::where('created_at', '>=', Carbon::now()->subMonth())->count()
            ];
        });

        return response()->json([
            'spheres' => $spheres,
            'statistics' => $statistics
        ]);
    }
}
