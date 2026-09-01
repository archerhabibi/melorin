<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * سوپرادمین اولیه‌ی پنل Filament را از config('melorin.super_admin')
 * (که خودش از ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD در .env می‌آید)
 * می‌سازد — اگر ایمیل/رمز تنظیم نشده باشند، بی‌خطر رد می‌شود.
 *
 * idempotent: اگر ادمینی با همین ایمیل از قبل وجود داشته باشد، دست
 * نمی‌زند و رمز موجود را بازنویسی نمی‌کند — دقیقاً همان رفتار بخش
 * «ساخت اولین Super Admin» در install.sh روی سرور.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('melorin.super_admin.email');
        $password = config('melorin.super_admin.password');

        if (! $email || ! $password) {
            $this->command?->line('AdminSeeder: ADMIN_EMAIL/ADMIN_PASSWORD در .env تنظیم نشده — رد شد.');

            return;
        }

        if (Admin::where('email', $email)->exists()) {
            $this->command?->line("AdminSeeder: ادمینی با ایمیل {$email} از قبل وجود دارد — بدون تغییر.");

            return;
        }

        Admin::create([
            'name' => config('melorin.super_admin.name', 'Administrator'),
            'email' => $email,
            // مدل Admin فیلد password را cast('hashed') کرده، پس نیازی
            // به Hash::make() دستی نیست.
            'password' => $password,
            'is_super_admin' => true,
        ]);

        $this->command?->info("AdminSeeder: سوپر ادمین ساخته شد: {$email}");
    }
}
