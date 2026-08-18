<?php

namespace App\Filament\Resources\ServerPanelResource\Pages;

use App\Filament\Resources\ServerPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerPanels extends ListRecords
{
    protected static string $resource = ServerPanelResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('افزودن سرور / پنل')];
    }
}
