<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'price', 'traffic_gb', 'duration_days',
        'protocol_id', 'status', 'sale_limit', 'allowed_panel_ids', 'naming_mode',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'traffic_gb' => 'decimal:2',
        'allowed_panel_ids' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class);
    }

    public function resellerPrices(): HasMany
    {
        return $this->hasMany(ResellerProductPrice::class);
    }

    /** قیمت نهایی برای یک نماینده‌ی مشخص؛ اگر تعریف نشده بود، قیمت پایه برگردانده می‌شود */
    public function priceForReseller(?Reseller $reseller): float
    {
        if (! $reseller) {
            return (float) $this->price;
        }

        $custom = $this->resellerPrices()->where('reseller_id', $reseller->id)->first();

        return $custom ? (float) $custom->custom_price : (float) $this->price;
    }
}
