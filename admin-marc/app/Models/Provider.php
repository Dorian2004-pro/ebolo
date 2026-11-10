<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['verification_token', 'bank_account_number', 'bank_routing_number'];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'serves_at_customer_location' => 'boolean',
            'serves_at_business_location' => 'boolean',
            'auto_accept_bookings' => 'boolean',
            'portfolio_images' => 'array',
            'notification_preferences' => 'array',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'provider_categories')->withTimestamps();
    }
}
