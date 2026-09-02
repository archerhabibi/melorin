<?php

return [
    // بدون این کلید، پکیج irazasyed/telegram-bot-sdk (که به‌صورت خودکار
    // هم Provider خودش را رجیستر می‌کند) به‌دنبال باتی به نام پیش‌فرض
    // «mybot» می‌گردد و چون چنین باتی تعریف نشده با خطای
    // «Bot [mybot] is not configured» و ۵۰۰ روی وبهوک شکست می‌خورد —
    // این باگ واقعی در تست روی سرور واقعی رخ داد و علتش همین بود.
    'default' => 'main',

    /*
     * توکن ربات اصلی. توکن‌های ربات‌های نمایندگی (بند ۱۸ سند) در جدول
     * resellers.bot_token نگهداری می‌شوند، نه اینجا — چون تعدادشان پویاست.
     */
    'bots' => [
        'main' => [
            'token' => env('TELEGRAM_MAIN_BOT_TOKEN'),
            'username' => env('TELEGRAM_MAIN_BOT_USERNAME'),
        ],
    ],

    // برای راستی‌آزمایی درخواست‌های webhook (هدر X-Telegram-Bot-Api-Secret-Token)
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    /*
     * شناسه‌های عددی تلگرام (Telegram ID، نه username) ادمین‌هایی که با
     * ارسال عبارت «ادمین» به ربات، به منوی مدیریت دسترسی دارند (بند ۳
     * سند نیازمندی). با کاما جدا می‌شوند، مثال در .env:
     *   TELEGRAM_ADMIN_IDS=123456789,987654321
     *
     * توجه: این جدا از مدل Admin (کاربران پنل Filament) است — اینجا فقط
     * تشخیص می‌دهد که آیا این کاربر تلگرام اجازه‌ی دیدن پیام «به پنل
     * مدیریت مراجعه کنید» را دارد یا نه. مدیریت واقعی همچنان از طریق
     * پنل وب (بند ۲۲) انجام می‌شود.
     */
    'admin_ids' => array_filter(array_map(
        'trim',
        explode(',', env('TELEGRAM_ADMIN_IDS', ''))
    )),
];
