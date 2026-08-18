<?php

namespace App\Channels\TelegramBot\Support;

use Telegram\Bot\Keyboard\Keyboard;

/**
 * منوهای ربات اصلی — دقیقاً مطابق بند ۳.۱ سند نیازمندی.
 */
class Keyboards
{
    public static function mainMenu(): Keyboard
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->row(['🛒 خرید اکانت', '🔍 استعلام و تمدید اکانت'])
            ->row(['💰 کیف پول و شارژ حساب', '👤 حساب کاربری'])
            ->row(['🎁 دعوت از دوستان / زیرمجموعه‌گیری', '🧪 دریافت اکانت تست'])
            ->row(['📜 قوانین خرید و آموزش', '🎧 پشتیبانی'])
            ->row(['🤖 ربات مشتری / نماینده']);
    }

    /** لیست دسته‌بندی‌ها (سبد فروش) به‌صورت این‌لاین کیبورد */
    public static function categoryList(iterable $categories): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($categories as $category) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $category->name,
                    'callback_data' => "buy:category:{$category->id}",
                ]),
            ]);
        }

        return $keyboard;
    }

    /** لیست محصولات/تعرفه‌های یک دسته‌بندی */
    public static function productList(iterable $products): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($products as $product) {
            $label = sprintf(
                '%s — %s تومان (%s روز%s)',
                $product->name,
                number_format((float) $product->price),
                $product->duration_days,
                $product->traffic_gb ? ", {$product->traffic_gb} گیگ" : ''
            );

            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $label,
                    'callback_data' => "buy:product:{$product->id}",
                ]),
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => '⬅️ بازگشت', 'callback_data' => 'buy:back_to_categories']),
        ]);

        return $keyboard;
    }

    /**
     * لیست سرورهای مجاز یک دسته‌بندی، برای حالت انتخاب دستی (بند ۶.۱).
     * value کال‌بک به‌صورت «شناسه‌محصول_شناسه‌سرور» است، چون routeCallback
     * فقط تا سومین «:» می‌شکند و همان یک تکه‌ی آخر باید هر دو شناسه را
     * حمل کند.
     */
    public static function serverList(iterable $panels, int $productId): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($panels as $panel) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $panel->name,
                    'callback_data' => "buy:server:{$productId}_{$panel->id}",
                ]),
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => '⬅️ بازگشت', 'callback_data' => 'buy:back_to_categories']),
        ]);

        return $keyboard;
    }

    public static function walletTopupAmounts(): Keyboard
    {
        return Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton(['text' => '۵۰,۰۰۰ تومان', 'callback_data' => 'wallet:amount:50000']),
                Keyboard::inlineButton(['text' => '۱۰۰,۰۰۰ تومان', 'callback_data' => 'wallet:amount:100000']),
            ])
            ->row([
                Keyboard::inlineButton(['text' => '۲۰۰,۰۰۰ تومان', 'callback_data' => 'wallet:amount:200000']),
                Keyboard::inlineButton(['text' => '✏️ مبلغ دلخواه', 'callback_data' => 'wallet:amount:custom']),
            ]);
    }

    public static function paymentMethods(iterable $methods): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($methods as $method) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $method->name,
                    'callback_data' => "wallet:method:{$method->id}",
                ]),
            ]);
        }

        return $keyboard;
    }

    public static function accountActions(int $accountId): Keyboard
    {
        return Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton(['text' => '♻️ تمدید', 'callback_data' => "account:renew:{$accountId}"]),
                Keyboard::inlineButton(['text' => '📱 دریافت کانفیگ / QR', 'callback_data' => "account:config:{$accountId}"]),
            ]);
    }
}
