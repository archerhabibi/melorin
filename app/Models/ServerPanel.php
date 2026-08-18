<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class ServerPanel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'panel_type', 'host', 'port', 'credentials', 'status',
        'capacity', 'account_limit_per_user', 'health_status',
        'active_accounts_count', 'extra_settings',
    ];

    protected $casts = [
        'credentials' => 'encrypted',
        'extra_settings' => 'array',
    ];

    public function protocols(): BelongsToMany
    {
        return $this->belongsToMany(Protocol::class, 'server_panel_protocol')
            ->withPivot('settings')->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_server_panel');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
