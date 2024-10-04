<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    protected $fillable = [
        'sphere_id',
        'user_id',
        'reaction_type',
    ];

    public function sphere(): BelongsTo
    {
        return $this->belongsTo(Sphere::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
