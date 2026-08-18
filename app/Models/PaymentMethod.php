<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'status', 'settings'];

    protected $casts = ['settings' => 'array'];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
