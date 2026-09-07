<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * تنظیمات تک‌رکوردیِ محتوای متنی ربات (الگوی singleton مثل
 * AffiliateSetting/TestAccountSetting). فعلاً فقط قوانین خرید — در
 * آینده هر متن دیگری که باید از پنل وب قابل‌ویرایش باشد، به همین جدول
 * اضافه می‌شود (بدون نیاز به جدول جدید برای هر پیام).
 */
class BotContentSetting extends Model
{
    protected $fillable = ['purchase_rules_text'];

    /** تنظیمات فعال سیستم (تک‌رکوردی) */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /**
     * پیش‌فرض دقیقاً همان متنی است که قبلاً در MiscHandler::rules()
     * هاردکد بود — تا وقتی ادمین از پنل چیزی ذخیره نکرده، خروجی ربات
     * هیچ تغییری نمی‌کند.
     */
    public function purchaseRulesText(): string
    {
        return $this->purchase_rules_text
            ?: "📜 قوانین خرید:\n\n۱. پس از خرید امکان بازگشت وجه وجود ندارد مگر در صورت خرابی سرویس.\n۲. اکانت‌ها فقط برای استفاده‌ی شخصی هستند.\n۳. برای هرگونه مشکل با پشتیبانی در تماس باشید.";
    }
}
