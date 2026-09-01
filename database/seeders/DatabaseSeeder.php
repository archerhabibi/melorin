<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // سوپرادمین اولیه، فقط اگر ADMIN_EMAIL/ADMIN_PASSWORD در .env
        // تنظیم شده باشند (نگاه کنید به database/seeders/AdminSeeder.php).
        $this->call([
            AdminSeeder::class,
        ]);

        // بقیه‌ی داده‌های اولیه (ServerPanel/Category/Product) طبق
        // چک‌لیست استقرار، دستی یا از طریق پنل ادمین وارد می‌شوند.
    }
}
