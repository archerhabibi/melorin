<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * طبق باگی که در تست واقعی روی پنل سنایی (3X-UI) پیدا شد: آدرس پنل
 * می‌تواند خودش پورت را داخل خودش داشته باشد (مثل
 * https://panel.example.com:2053/kharej) — در این حالت فیلد port باید
 * بتواند خالی بماند، وگرنه ادمین مجبور می‌شد یک پورت اضافه/غلط وارد کند
 * که باعث می‌شد SanaeiDriver دو بار پورت را به آدرس اضافه کند.
 *
 * از SQL خام (نه ->change()) استفاده شده تا نیازی به نصب doctrine/dbal
 * روی سرورهای در حال اجرا نباشد.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        match ($driver) {
            'mysql' => DB::statement('ALTER TABLE server_panels MODIFY port INT NULL'),
            'pgsql' => DB::statement('ALTER TABLE server_panels ALTER COLUMN port DROP NOT NULL'),
            default => null, // sqlite: ستون INTEGER در sqlite ذاتاً سخت‌گیر نیست، نیازی به تغییر نیست
        };
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        match ($driver) {
            'mysql' => DB::statement('ALTER TABLE server_panels MODIFY port INT NOT NULL DEFAULT 443'),
            'pgsql' => DB::statement('ALTER TABLE server_panels ALTER COLUMN port SET NOT NULL'),
            default => null,
        };
    }
};
