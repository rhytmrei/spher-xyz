<?php

namespace App\Http\Controllers;

use App\Events\SphereReactionEvent;
use App\Models\Reaction;
use App\Models\Sphere;
use Illuminate\Http\JsonResponse;

class RatingController extends Controller
{
    public function reaction(Sphere $sphere, string $type): JsonResponse
    {
        $userId = auth()->id();

        $sphere->load(['reactions' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }]);

        if ($existingReaction = $sphere->reactions->first()) {
            if ($existingReaction->reaction_type === $type) {
                $existingReaction->delete();
            } else {
                $existingReaction->update(['reaction_type' => $type]);
            }
        } else {
            Reaction::create([
                'sphere_id' => $sphere->id,
                'user_id' => $userId,
                'reaction_type' => $type,
            ]);
        }

        $total = $sphere->reactions()
            ->selectRaw("SUM(CASE WHEN reaction_type = 'like' THEN 1 ELSE 0 END) as likes_count")
            ->selectRaw("SUM(CASE WHEN reaction_type = 'dislike' THEN 1 ELSE 0 END) as dislikes_count")
            ->first();

        broadcast(new SphereReactionEvent($sphere->id, $total->toArray()))->toOthers();

        return response()->json($total);
    }
}
