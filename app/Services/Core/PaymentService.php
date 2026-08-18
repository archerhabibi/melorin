<?php

namespace App\Services\Core;

use App\Events\PaymentConfirmed;
use App\Models\Admin;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Core\Payments\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * PaymentService — تنها نقطه‌ی مجاز در سیستم برای ایجاد، تایید، رد و
 * بازگشتِ پرداخت (بند ۱۲ سند نیازمندی). مطابق اصل معماری بند ۳۴، هیچ
 * کانالی (ربات، سایت، نماینده) نباید مستقیم با یک درگاه پرداخت صحبت کند
 * یا وضعیت payments/wallets را دستی تغییر دهد.
 */
class PaymentService
{
    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * ایجاد یک پرداخت جدید و شروع آن نزد درگاه.
     *
     * @return array{payment: Payment, initiation: \App\DataTransferObjects\GatewayInitiationResult}
     */
    public function initiate(
        User $user,
        PaymentMethod $method,
        float $amount,
        string $purpose,
        ?Model $reference = null,
        ?string $receiptImage = null,
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('مبلغ باید بزرگ‌تر از صفر باشد.');
        }

        if (! in_array($purpose, ['order', 'wallet_charge'], true)) {
            throw new \InvalidArgumentException("purpose نامعتبر: {$purpose}");
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_method_id' => $method->id,
            'amount' => $amount,
            'purpose' => $purpose,
            'receipt_image' => $receiptImage,
            'status' => 'pending',
        ]);

        $gateway = PaymentGatewayFactory::make($method);
        $result = $gateway->initiate($payment);

        $payment->update([
            'gateway_reference' => $result->gatewayReference,
            'gateway_response' => $result->rawResponse,
        ]);

        return ['payment' => $payment, 'initiation' => $result];
    }

    /**
     * تایید دستی توسط ادمین برای درگاه‌های دستی مثل کارت‌به‌کارت
     * (بند ۱۲: «بررسی رسید»، «تأیید پرداخت»).
     */
    public function confirmManual(Payment $payment, Admin $admin): Payment
    {
        $gateway = PaymentGatewayFactory::make($payment->paymentMethod);

        if (! $gateway->isManual()) {
            throw new \LogicException('این پرداخت از یک درگاه آنلاین است و باید از طریق callback تایید شود، نه دستی.');
        }

        $this->assertPending($payment);

        return $this->finalize($payment, $admin);
    }

    /**
     * تایید از طریق callback یک درگاه آنلاین (مثل Zarinpal).
     * اگر تایید درگاه ناموفق باشد، پرداخت خودکار rejected می‌شود.
     */
    public function handleCallback(Payment $payment, array $callbackData): Payment
    {
        $gateway = PaymentGatewayFactory::make($payment->paymentMethod);

        if ($gateway->isManual()) {
            throw new \LogicException('این پرداخت دستی است و callback ندارد؛ از confirmManual استفاده کنید.');
        }

        // callback تکراری روی پرداخت already-confirmed نباید دوباره کیف پول را شارژ کند
        if ($payment->status === 'confirmed') {
            return $payment;
        }

        $this->assertPending($payment);

        $verification = $gateway->verify($payment, $callbackData);

        if (! $verification->success) {
            return $this->reject($payment, null, $verification->errorMessage);
        }

        $payment->update(['gateway_response' => $verification->rawResponse]);

        return $this->finalize($payment);
    }

    public function reject(Payment $payment, ?Admin $admin = null, ?string $reason = null): Payment
    {
        $this->assertPending($payment);

        $payment->update([
            'status' => 'rejected',
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now(),
        ]);

        return $payment;
    }

    /**
     * بازگشت وجه یک پرداخت تاییدشده (بند ۱۲). فقط برای purpose=wallet_charge
     * معنا دارد چون مستقیماً از کیف پول کاربر کسر می‌کند؛ اگر کاربر آن
     * موجودی را قبلاً خرج کرده باشد، InsufficientBalanceException پرتاب
     * می‌شود — یعنی بازگشت وجه باید با تنظیم دستی موجودی توسط ادمین دنبال شود.
     */
    public function refund(Payment $payment, ?Admin $admin = null): Payment
    {
        if ($payment->status !== 'confirmed') {
            throw new \LogicException('فقط پرداخت‌های تایید‌شده قابل بازگشت وجه هستند.');
        }

        return DB::transaction(function () use ($payment, $admin) {
            if ($payment->purpose === 'wallet_charge') {
                $this->walletService->adminAdjust(
                    $payment->user,
                    -1 * (float) $payment->amount,
                    $payment,
                    "بازگشت وجه پرداخت #{$payment->id}"
                );
            }

            $payment->update([
                'status' => 'refunded',
                'reviewed_by' => $admin?->id ?? $payment->reviewed_by,
                'reviewed_at' => now(),
            ]);

            return $payment;
        });
    }

    protected function finalize(Payment $payment, ?Admin $admin = null): Payment
    {
        return DB::transaction(function () use ($payment, $admin) {
            $payment->update([
                'status' => 'confirmed',
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
            ]);

            // فقط شارژ کیف پول مستقیماً داخل هسته انجام می‌شود؛ برای
            // purpose=order، تصمیم این‌که سفارش چطور تکمیل شود به کانالی
            // که آن سفارش را ساخته (از طریق PaymentConfirmed) واگذار می‌شود.
            if ($payment->purpose === 'wallet_charge') {
                $this->walletService->charge(
                    $payment->user,
                    (float) $payment->amount,
                    $payment,
                    "شارژ کیف پول — پرداخت #{$payment->id}"
                );
            }

            PaymentConfirmed::dispatch($payment->fresh());

            return $payment->fresh();
        });
    }

    protected function assertPending(Payment $payment): void
    {
        if ($payment->status !== 'pending') {
            throw new \LogicException("این پرداخت در وضعیت pending نیست (وضعیت فعلی: {$payment->status}).");
        }
    }
}
