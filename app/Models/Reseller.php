<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphOne};

class Reseller extends Model
{
    protected $fillable = ['user_id', 'bot_token', 'status', 'min_sale_price_rule'];

    protected $casts = [
        'bot_token' => 'encrypted',
        'min_sale_price_rule' => 'array',
    ];

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(User::class, 'reseller_id');
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ResellerProductPrice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
