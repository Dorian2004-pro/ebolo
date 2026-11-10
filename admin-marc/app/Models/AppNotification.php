<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasUuids;

    protected $table = 'app_notifications';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array', 'is_read' => 'boolean', 'is_archived' => 'boolean', 'read_at' => 'datetime'];
    }
}
