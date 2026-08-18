<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramConversationState extends Model
{
    protected $fillable = ['telegram_chat_id', 'user_id', 'step', 'payload'];

    protected $casts = [
        'telegram_chat_id' => 'integer',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
