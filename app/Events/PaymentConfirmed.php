<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * طبق اصل معماری بند ۳۴، PaymentService نباید مستقیماً بداند نتیجه‌ی یک
 * پرداخت با purpose=order باید چه کاری در ربات/سایت/نماینده انجام دهد.
 * به‌جایش این رویداد را منتشر می‌کند؛ هر کانال Listener خودش را ثبت
 * می‌کند (مثلاً: تحویل کانفیگ در ربات، به‌روزرسانی صفحه‌ی سفارش در سایت).
 */
class PaymentConfirmed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Payment $payment) {}
}
