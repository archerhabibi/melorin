<?php

return [
    /*
     * سوپر ادمین اولیه‌ی پنل Filament (App\Models\Admin)، اختیاری.
     * اگر ADMIN_EMAIL و ADMIN_PASSWORD در .env تعریف شده باشند،
     * database/seeders/AdminSeeder.php هنگام اجرای «php artisan
     * db:seed» یک سوپرادمین با این مشخصات می‌سازد — فقط اگر از قبل
     * ادمینی با همین ایمیل وجود نداشته باشد (رمز موجود بازنویسی
     * نمی‌شود).
     *
     * مناسب برای محیط توسعه/لوکال (مثل Laragon روی ویندوز) که اجرای
     * install.sh تعاملی (که برای سرور Ubuntu نوشته شده) ممکن نیست.
     * روی سرور تولید، ساخت سوپرادمین همچنان عمدتاً از طریق پرامپت
     * تعاملی install.sh انجام می‌شود؛ این تنها یک مسیر جایگزین/مکمل
     * است برای زمانی که ADMIN_EMAIL/ADMIN_PASSWORD در .env مقداردهی
     * شده باشند (مثلاً استقرار خودکار).
     */
    'super_admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
