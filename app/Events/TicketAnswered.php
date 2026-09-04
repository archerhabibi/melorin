<?php

namespace App\Events;

use App\Models\TicketMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * وقتی ادمین از پنل Filament به یک تیکت پاسخ می‌دهد منتشر می‌شود. کاربر
 * فقط از طریق ربات تلگرام تیکت ثبت کرده، پس تنها راهی که او می‌تواند
 * از پاسخ مطلع شود همین رویداد و Listener مربوطه است — دقیقاً همان
 * الگوی PaymentConfirmed → NotifyUserOfPaymentConfirmation.
 */
class TicketAnswered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly TicketMessage $message) {}
}
