<?php

namespace App\Services\Core\Panels;

use App\Models\ServerPanel;

interface SupportsUsernameAvailability
{
    /**
     * بررسی می‌کند که آیا username روی پنل از قبل وجود دارد یا خیر.
     */
    public function usernameExists(
        ServerPanel $panel,
        string $username
    ): bool;
}
