<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerProductPrice extends Model
{
    protected $fillable = ['reseller_id', 'product_id', 'custom_price'];

    protected $casts = ['custom_price' => 'decimal:2'];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
