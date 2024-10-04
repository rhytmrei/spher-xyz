<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('sphere.{sphere}.{type}', function (\App\Models\User $user, \App\Models\Sphere $sphere) {
    return $sphere->user_id === $user->id;
});
