<?php

namespace App\Services\Core;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * WalletService
 *
 * تنها نقطه‌ی مجاز در کل سیستم برای تغییر موجودی کیف پول (User یا Reseller).
 * هیچ بخش دیگری (ربات، پنل، سایت) نباید مستقیم فیلد balance را تغییر دهد؛
 * طبق اصل معماری سند نیازمندی (بند ۲۸ و ۳۴)، هر تغییر موجودی باید از این
 * سرویس عبور کند تا سابقه‌ی تراکنش (wallet_transactions) همیشه با balance
 * واقعی هماهنگ بماند.
 */
class WalletService
{
    /** کیف پول یک مالک (User یا Reseller) را برمی‌گرداند؛ اگر وجود نداشت می‌سازد */
    public function getOrCreateWallet(Model $owner): Wallet
    {
        return Wallet::firstOrCreate([
            'owner_type' => $owner::class,
            'owner_id' => $owner->getKey(),
        ], [
            'balance' => 0,
        ]);
    }

    public function balance(Model $owner): float
    {
        return (float) $this->getOrCreateWallet($owner)->balance;
    }

    /** شارژ کیف پول (بند ۱۱) */
    public function charge(Model $owner, float $amount, ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        $this->assertPositive($amount);

        return $this->applyTransaction($owner, 'charge', $amount, $reference, $description);
    }

    /**
     * کسر مبلغ برای خرید (بند ۹) — در صورت کمبود موجودی خطا می‌دهد.
     *
     * توجه: بررسی کفایت موجودی و کسر آن هر دو داخل applyTransaction() و
     * زیر یک قفل ردیف صحیح (owner_type + owner_id) انجام می‌شود، تا در
     * حالت درخواست‌های هم‌زمان هرگز کیف پول اشتباه خوانده یا تغییر نکند
     * (نسخه‌ی قبلی این متد یک لاک جدا و نادرست روی کل جدول wallets
     * می‌زد که می‌توانست کیف پول کاربر دیگری را قفل/چک کند — رفع شد).
     *
     * @throws InsufficientBalanceException
     */
    public function purchase(Model $owner, float $amount, ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        $this->assertPositive($amount);

        return $this->applyTransaction($owner, 'purchase', -$amount, $reference, $description);
    }

    /** بازگشت وجه (بند ۱۲) */
    public function refund(Model $owner, float $amount, ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        $this->assertPositive($amount);

        return $this->applyTransaction($owner, 'refund', $amount, $reference, $description);
    }

    /** واریز کمیسیون یا پاداش زیرمجموعه‌گیری (بند ۱۳) */
    public function addCommission(Model $owner, float $amount, ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        $this->assertPositive($amount);

        return $this->applyTransaction($owner, 'commission', $amount, $reference, $description);
    }

    public function addReferralBonus(Model $owner, float $amount, ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        $this->assertPositive($amount);

        return $this->applyTransaction($owner, 'referral_bonus', $amount, $reference, $description);
    }

    /**
     * تغییر دستی موجودی توسط ادمین (بند ۱۴: افزایش/کاهش موجودی).
     * amount می‌تواند مثبت یا منفی باشد.
     */
    public function adminAdjust(Model $owner, float $amount, ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        return $this->applyTransaction($owner, 'admin_adjust', $amount, $reference, $description);
    }

    /**
     * هسته‌ی مشترک همه‌ی عملیات: در یک تراکنش دیتابیسی، موجودی را با قفل
     * ردیف (lockForUpdate) به‌روزرسانی می‌کند و رکورد wallet_transactions
     * را با balance_after ثبت می‌کند تا گردش حساب همیشه قابل بازسازی باشد.
     */
    protected function applyTransaction(
        Model $owner,
        string $type,
        float $signedAmount,
        ?Model $reference,
        ?string $description,
        ?Wallet $lockedWallet = null
    ): WalletTransaction {
        return DB::transaction(function () use ($owner, $type, $signedAmount, $reference, $description, $lockedWallet) {
            $wallet = $lockedWallet ?? Wallet::query()
                ->where('owner_type', $owner::class)
                ->where('owner_id', $owner->getKey())
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $this->getOrCreateWallet($owner);
                $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->first();
            }

            $newBalance = (float) $wallet->balance + $signedAmount;

            if ($newBalance < 0) {
                throw new InsufficientBalanceException;
            }

            $wallet->update(['balance' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $signedAmount,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
            ]);
        });
    }

    protected function assertPositive(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('مبلغ باید بزرگ‌تر از صفر باشد.');
        }
    }
}
