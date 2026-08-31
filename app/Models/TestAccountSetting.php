<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تنظیمات تک‌رکوردی اکانت تست (بند «اکانت تست» — گزارش Audit فاز A،
 * سند 07). الگوی singleton دقیقاً مثل AffiliateSetting.
 */
class TestAccountSetting extends Model
{
    protected $fillable = ['enabled', 'product_id', 'max_per_user'];

    protected $casts = [
        'enabled' => 'boolean',
        'max_per_user' => 'integer',
    ];

    /** تنظیمات فعال سیستم (تک‌رکوردی) */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * فقط وقتی واقعاً قابل‌استفاده است که هم enabled=true باشد و هم یک
     * محصول تست انتخاب شده باشد — تا دکمه‌ی منو هرگز به یک حالت خراب
     * (enabled بدون product) منتهی نشود.
     */
    public function isUsable(): bool
    {
        return $this->enabled && $this->product_id !== null;
    }
}
