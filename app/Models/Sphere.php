<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Sphere extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function ($sphere) {
            $uuid = (string) Str::uuid();

            $sphere->id = $uuid;

            if (empty($sphere->title)) {
                $sphere->title = "Sphere $uuid";
            }
        });
    }

    protected $fillable = [
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'texture_colors' => 'json',
        'distance' => 'float',
    ];

    protected $hidden = [
        'updated_at',
        'primaryImage',
        'reactions',
    ];

    protected $appends = [
        'image_src',
    ];

    public static function defaultImage(): string
    {
        return 'sphere.webp';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }

    public function scopeWithReactionCounts($query)
    {
        return $query->withCount([
            'reactions as likes_count' => function ($query) {
                $query->where('reaction_type', 'like');
            },
            'reactions as dislikes_count' => function ($query) {
                $query->where('reaction_type', 'dislike');
            },
        ]);
    }

    public function scopeWithRating($query)
    {
        return $query->withCount([
            'reactions as rating' => function ($query) {
                $query->select(DB::raw('SUM(CASE WHEN reaction_type = \'like\' THEN 1 ELSE 0 END) -
                    SUM(CASE WHEN reaction_type = \'dislike\' THEN 1 ELSE 0 END)'));
            },
        ]);
    }

    public function scopeWithUserReactions($query, $userId = null)
    {
        return $query->withCount([
            'reactions as liked_by_user' => function ($query) use ($userId) {
                $query->where('reaction_type', 'like')->where('user_id', $userId);
            },
            'reactions as disliked_by_user' => function ($query) use ($userId) {
                $query->where('reaction_type', 'dislike')->where('user_id', $userId);
            },
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getImageSrcAttribute(): string
    {
        $image = $this->primaryImage->path ?? self::defaultImage();

        return Storage::url($image);
    }

    private function morphImage(string $type): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('type', $type);
    }

    public function primaryImage(): MorphOne
    {
        return $this->morphImage('image');
    }

    public function activeTexture(): MorphOne
    {
        return $this->morphImage('active');
    }

    public function textureHistory(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->whereIn('type', ['texture', 'active'])
            ->orderBy('id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }
}
