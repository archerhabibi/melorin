<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'settings', 'server_selection_mode', 'naming_mode'];

    protected $casts = ['settings' => 'array'];

    public function serverPanels(): BelongsToMany
    {
        return $this->belongsToMany(ServerPanel::class, 'category_server_panel');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
