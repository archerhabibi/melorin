<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * طبق همان اصل معماری بند ۳۴ که برای PaymentConfirmed استفاده شده:
 * MiscHandler (لایه‌ی ربات) نباید مستقیماً بداند که با ثبت یک تیکت چه
 * کسی/چطور باید مطلع شود — فقط رویداد را منتشر می‌کند. در حال حاضر
 * فقط یک Listener (اطلاع‌رسانی به ادمین‌های تلگرام) به آن گوش می‌دهد،
 * ولی کانال‌های دیگر (مثلاً ایمیل) می‌توانند بعداً بدون تغییر در
 * MiscHandler اضافه شوند.
 */
class TicketCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Ticket $ticket) {}
}
