<?php

namespace App\Providers;

use App\Services\Core\ServerSelection\LeastActiveAccountsStrategy;
use App\Services\Core\ServerSelection\ServerSelectionStrategy;
use Illuminate\Support\ServiceProvider;

/**
 * ثبت Bindingهای هسته‌ی ملورین. تغییر الگوریتم انتخاب سرور (بند ۶.۳ سند)
 * در فاز‌های بعدی، تنها با تغییر خط زیر انجام می‌شود.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServerSelectionStrategy::class, LeastActiveAccountsStrategy::class);
    }
}
