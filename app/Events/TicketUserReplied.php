<?php

namespace App\Events;

use App\Models\TicketMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * وقتی کاربر به یک تیکت موجود (نه ساخت تیکت جدید) پیام اضافه می‌کند
 * منتشر می‌شود — عمداً از TicketCreated جدا نگه داشته شده، چون متن
 * درستِ اعلان برای ادمین متفاوت است («پیام جدید در تیکت #X»، نه
 * «تیکت جدید #X»؛ برای «تیکت جدید» ادمین‌های دیگر هم باید بدانند از
 * صفر شروع شده، ولی برای «پیام جدید» فقط یادآوری کافی است).
 */
class TicketUserReplied
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly TicketMessage $message) {}
}
