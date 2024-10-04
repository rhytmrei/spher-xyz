<?php

namespace App\Http\Controllers;

use App\Models\Sphere;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index(int $limit = 40): JsonResponse
    {
        $spheres = Cache::remember('spheres.home', now(), function () use ($limit) {
            $spheres = Sphere::orderBy('created_at', 'desc')->with('primaryImage')->limit($limit)->get('id')->pluck('image_src');

            $defaultImageUrl = Storage::url(Sphere::defaultImage());

            return $spheres->pad($limit, $defaultImageUrl);
        });

        return response()->json($spheres);
    }
}
