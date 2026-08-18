<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = true;
    const UPDATED_AT = null; // لاگ‌ها هرگز ویرایش نمی‌شوند

    protected $fillable = [
        'actor_type', 'actor_id', 'action', 'target_type', 'target_id',
        'before', 'after', 'ip_address',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
