<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UploadAvatarRequest;
use App\Models\Image;
use App\Models\Sphere;
use App\Services\Contracts\FileServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $pathImages = $user->spheres()->select(['id'])->get()->map(function ($sphere) {
            $imagePath = "spheres/{$sphere->id}/texture.gif";

            return Storage::exists($imagePath) ? $imagePath : null;
        })->filter();

        return response()->json([
            'user' => $user,
            'url' => Storage::url(''),
            'gif_avatars' => $pathImages,
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request, FileServiceContract $fileService): JsonResponse
    {
        $user = auth()->user();

        if ($request->hasFile('image') || $request->filled('gif_path')) {
            $path = $request->hasFile('image')
                ? $fileService->upload($request->file('image'), 'users')
                : $request->input('gif_path');

            $type = $request->filled('gif_path') ? 'sphereGifPath' : null;
        } else {
            return response()->json(['error' => 'No file or URL provided.'], 400);
        }

        if ($old = $user->image) {
            if ($old->path === $path) {
                return response()->json(['url' => Storage::url($path)]);
            }

            if (Image::where('path', $old->path)->where('type', 'gif')->exists()) {
                $old->deleteQuietly();
            } else {
                $old->delete();
            }
        }

        $user->image()->create(['path' => $path, 'type' => $type]);

        return response()->json(['url' => Storage::url($path)]);
    }
}
