<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateSetting extends Model
{
    protected $fillable = [
        'customer_bonus_amount', 'referrer_bonus_amount',
        'commission_percent', 'commission_validity_days',
    ];

    protected $casts = [
        'customer_bonus_amount' => 'decimal:2',
        'referrer_bonus_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
    ];

    /** تنظیمات فعال سیستم (تک‌رکوردی) */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
