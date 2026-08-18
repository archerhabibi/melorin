<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class Protocol extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    public function serverPanels(): BelongsToMany
    {
        return $this->belongsToMany(ServerPanel::class, 'server_panel_protocol')
            ->withPivot('settings')->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
