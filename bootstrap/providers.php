<?php

use App\Channels\TelegramBot\TelegramBotServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\CoreServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CoreServiceProvider::class,
    TelegramBotServiceProvider::class,
];
