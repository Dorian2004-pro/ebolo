<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtisanProfile extends Model
{
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'identity_submitted_at' => 'datetime',
            'availability' => 'array',
        ];
    }

    protected $fillable = [
        'user_id',
        'specialty',
        'bio',
        'experience_years',
        'rating',
        'is_verified',
        'status',
        'rejection_reason',
        'verified_at',
        'location',
        'cover_image',
        'identity_type',
        'identity_recto_path',
        'identity_verso_path',
        'identity_submitted_at',
        'identity_rejection_reason',
        'availability',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'artisan_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'artisan_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'artisan_id');
    }

    public function galleryImages()
    {
        return $this->hasMany(GalleryImage::class, 'artisan_id');
    }
}
