<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $appends = [
        'image_src',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'image',
    ];

    public function getRouteKeyName(): string
    {
        return 'name';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function defaultImage(): string
    {
        return 'user.webp';
    }

    public function image(): MorphOne
    {
        return $this->MorphOne(Image::class, 'imageable');
    }

    public function getImageSrcAttribute(): string
    {
        $image = $this->image->path ?? 'user.webp';

        return Storage::url($image);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function spheres(): HasMany
    {
        return $this->hasMany(Sphere::class);
    }
}
