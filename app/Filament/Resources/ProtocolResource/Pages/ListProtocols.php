<?php

namespace App\Filament\Resources\ProtocolResource\Pages;

use App\Filament\Resources\ProtocolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProtocols extends ListRecords
{
    protected static string $resource = ProtocolResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('افزودن پروتکل')];
    }
}
