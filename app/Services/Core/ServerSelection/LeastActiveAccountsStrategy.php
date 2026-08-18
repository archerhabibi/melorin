<?php

namespace App\Services\Core\ServerSelection;

use App\Models\Category;
use App\Models\ServerPanel;

/**
 * معیار پیش‌فرض نسخه اولیه (بند ۶.۲): سروری که کمترین تعداد اکانت فعال
 * را دارد انتخاب می‌شود، تا بار بین سرورها متعادل توزیع شود.
 */
class LeastActiveAccountsStrategy implements ServerSelectionStrategy
{
    public function select(Category $category): ?ServerPanel
    {
        return $category->serverPanels()
            ->where('status', 'active')
            ->orderBy('active_accounts_count')
            ->first();
    }
}
