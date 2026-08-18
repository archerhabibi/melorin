<?php

namespace App\Services\Core\ServerSelection;

use App\Models\Category;
use App\Models\ServerPanel;

/**
 * قرارداد الگوریتم انتخاب خودکار سرور (بند ۶.۲ و ۶.۳ سند نیازمندی).
 * افزودن معیار جدید (مصرف، سلامت سرور، ظرفیت و ...) یعنی ساخت یک کلاس
 * تازه که این Interface را پیاده می‌کند — بدون تغییر AccountService.
 */
interface ServerSelectionStrategy
{
    /** از میان سرورهای مجاز یک دسته‌بندی، مناسب‌ترین را انتخاب می‌کند */
    public function select(Category $category): ?ServerPanel;
}
