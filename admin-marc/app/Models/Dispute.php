<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasUuids;

    public const OPEN_STATUSES = ['open', 'investigating'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount_disputed' => 'decimal:2',
            'evidence' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function raiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function against(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_against');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class);
    }
}
