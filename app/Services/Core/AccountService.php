<?php

namespace App\Services\Core;

use App\DataTransferObjects\PanelAccountRequest;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Account;
use App\Models\Order;
use App\Models\Product;
use App\Models\ServerPanel;
use App\Models\User;
use App\Services\Core\Panels\PanelDriverFactory;
use App\Services\Core\ServerSelection\ServerSelectionStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AccountService — پیاده‌سازی جریان کامل «خرید و ساخت اکانت» (بند ۹ سند
 * نیازمندی). این تنها نقطه‌ی مجاز برای ساخت اکانت در کل سیستم است؛ طبق
 * اصل معماری بند ۳۴، هیچ کانال فروش (ربات، سایت، نماینده) مجاز نیست این
 * منطق را مستقیم پیاده‌سازی کند.
 */
class AccountService
{
    public function __construct(
        protected WalletService $walletService,
        protected ServerSelectionStrategy $serverSelection,
    ) {
    }

    /**
     * جریان کامل خرید:
     * کاربر → انتخاب محصول → کسر از کیف پول → انتخاب سرور →
     * ساخت اکانت روی پنل → ثبت سفارش و اکانت.
     *
     * @throws InsufficientBalanceException
     * @throws \RuntimeException در صورت شکست ساخت اکانت روی پنل
     */
    public function purchase(
        User $user,
        Product $product,
        ?ServerPanel $manualPanel = null,
        string $salesChannel = 'main_bot',
        ?\App\Models\Reseller $reseller = null
    ): Account {
        return DB::transaction(function () use ($user, $product, $manualPanel, $salesChannel, $reseller) {

            $soldPrice = $product->priceForReseller($reseller);

            // ۱. انتخاب سرور — دستی یا خودکار بسته به تنظیمات دسته‌بندی (بند ۶)
            $panel = $manualPanel ?? $this->serverSelection->select($product->category);

            if (! $panel) {
                throw new \RuntimeException('هیچ سرور فعالی برای این دسته‌بندی در دسترس نیست.');
            }

            // ۲. ثبت سفارش با وضعیت pending
            $order = Order::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'reseller_id' => $reseller?->id,
                'sales_channel' => $salesChannel,
                'base_price' => $product->price,
                'sold_price' => $soldPrice,
                'status' => 'pending',
            ]);

            // ۳. کسر مبلغ — از کیف پول نماینده (اگر فروش نمایندگی بود) یا کاربر
            $payer = $reseller ?? $user;
            $this->walletService->purchase(
                $payer,
                (float) $soldPrice,
                $order,
                "خرید محصول «{$product->name}» — سفارش #{$order->id}"
            );

            $order->update(['status' => 'paid']);

            // ۴. ساخت اکانت روی پنل واقعی
            // uuid را همین‌جا (نه داخل درایور) تولید می‌کنیم تا صرف‌نظر از
            // نوع پنل، از همان ابتدا در اختیار AccountService باشد و بتوان
            // آن را برای عملیات بعدی (تمدید/حذف روی پنل‌هایی مثل Sanaei که
            // با uuid کلاینت کار می‌کنند، نه فقط username) ذخیره کرد.
            $username = $this->generateUsername($user, $product);
            $clientUuid = (string) Str::uuid();
            // subId فقط برای پنل‌هایی مثل سنایی معنا دارد (شناسه‌ی
            // سرویس Subscription)، ولی چون تولیدش هیچ وابستگی به نوع پنل
            // ندارد، اینجا و نه داخل درایور تولید می‌شود — دقیقاً همان
            // دلیلی که uuid کلاینت هم اینجا تولید می‌شود.
            $subId = Str::random(16);

            $panelRequest = new PanelAccountRequest(
                username: $username,
                trafficBytes: $product->traffic_gb ? (int) ($product->traffic_gb * 1024 ** 3) : 0,
                expireTimestamp: now()->addDays($product->duration_days)->timestamp,
                note: "melorin-order-{$order->id}",
                extra: array_merge(
                    $panel->extra_settings ?? [],
                    ['uuid' => $clientUuid, 'tg_id' => $user->telegram_id, 'sub_id' => $subId]
                ),
            );

            $driver = PanelDriverFactory::make($panel->panel_type);
            $result = $driver->createAccount($panel, $panelRequest);

            if (! $result->success) {
                // بازگشت وجه در صورت شکست ساخت اکانت، تا کاربر متضرر نشود
                $this->walletService->refund($payer, (float) $soldPrice, $order, 'بازگشت به دلیل خطای ساخت اکانت');
                $order->update(['status' => 'failed']);

                throw new \RuntimeException("ساخت اکانت روی پنل ناموفق بود: {$result->errorMessage}");
            }

            // ۵. ثبت اکانت در دیتابیس مرکزی
            //
            // طبق درخواست صریح: چیزی که به کاربر تحویل داده می‌شود باید
            // لینک سابسکریپشن باشد (subscription_url)، نه کانفیگ خام تک‌
            // پروتکلی — چون کلاینت‌های VPN از روی لینک سابسکریپشن خودشان
            // را به‌روز نگه می‌دارند و با تغییر بعدیِ حجم/انقضا نیازی به
            // تحویل دوباره‌ی کانفیگ به کاربر نیست. rawResponse فقط برای
            // اشکال‌زدایی/سوابق نگه داشته می‌شود، نه برای نمایش به کاربر.
            $account = Account::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'server_panel_id' => $panel->id,
                'protocol_id' => $product->protocol_id,
                'panel_username' => $username,
                'panel_client_uuid' => $clientUuid,
                'subscription_id' => $result->panelExtra['subscription_id'] ?? null,
                'subscription_url' => $result->subscriptionUrl,
                'config_data' => json_encode(['raw' => $result->rawResponse]),
                'starts_at' => now(),
                'expires_at' => now()->addDays($product->duration_days),
                'traffic_gb' => $product->traffic_gb,
                'status' => 'active',
                'is_test' => false,
            ]);

            $order->update(['status' => 'account_created']);
            $panel->increment('active_accounts_count');

            return $account;
        });
    }

    /** تمدید اکانت موجود (بند ۱۰) */
    public function renew(Account $account, int $additionalDays, ?int $additionalTrafficGb = null): Account
    {
        $panel = $account->serverPanel;
        $driver = PanelDriverFactory::make($panel->panel_type);

        $newExpiry = $account->expires_at->isPast()
            ? now()->addDays($additionalDays)
            : $account->expires_at->addDays($additionalDays);

        $newTraffic = $additionalTrafficGb
            ? (float) $account->traffic_gb + $additionalTrafficGb
            : $account->traffic_gb;

        // از همان شناسه‌ای که موقع ساخت اکانت روی پنل ذخیره شده استفاده می‌کنیم
        // (نه telegram_id یا id داخلی ما که هیچ‌ربطی به شناسه‌ی پنل ندارند).
        $result = $driver->updateAccount($panel, $account->panel_username, new PanelAccountRequest(
            username: $account->panel_username,
            trafficBytes: $newTraffic ? (int) ($newTraffic * 1024 ** 3) : 0,
            expireTimestamp: $newExpiry->timestamp,
            extra: array_merge($panel->extra_settings ?? [], [
                'uuid' => $account->panel_client_uuid,
                'sub_id' => $account->subscription_id,
            ]),
        ));

        if (! $result->success) {
            throw new \RuntimeException("تمدید اکانت ناموفق بود: {$result->errorMessage}");
        }

        $account->update([
            'expires_at' => $newExpiry,
            'traffic_gb' => $newTraffic,
            'status' => 'active',
            // اگر قبلاً (اکانت‌های قدیمی‌تر) لینک سابسکریپشن ذخیره نشده
            // بود، همین‌جا از روی نتیجه‌ی تمدید دوباره پر می‌شود.
            'subscription_id' => $account->subscription_id ?? ($result->panelExtra['subscription_id'] ?? null),
            'subscription_url' => $account->subscription_url ?? $result->subscriptionUrl,
        ]);

        return $account;
    }

    protected function generateUsername(User $user, Product $product): string
    {
        return 'melorin_' . $user->id . '_' . Str::lower(Str::random(6));
    }
}
