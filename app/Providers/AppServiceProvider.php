<?php

namespace App\Providers;

use App\Events\PaymentConfirmed;
use App\Listeners\NotifyUserOfPaymentConfirmation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // بعد از تایید هر پرداخت (چه کارت‌به‌کارت دستی، چه درگاه آنلاین)،
        // به کاربر در تلگرام اطلاع داده شود.
        Event::listen(PaymentConfirmed::class, NotifyUserOfPaymentConfirmation::class);
    }
}
