<?php

namespace App\Filament\Resources\ServerPanelResource\Pages;

use App\Filament\Resources\ServerPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerPanel extends EditRecord
{
    protected static string $resource = ServerPanelResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * اگر ادمین مقدار جدیدی وارد نکرده باشد (فیلد را خالی گذاشته)، مقدار
     * فعلی credentials دست‌نخورده باقی می‌ماند — طبق پیام راهنمای فرم.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['panel_type'] ?? $this->record->panel_type) === 'sanaei') {
            $newToken = $this->data['credentials_api_token'] ?? null;

            if ($newToken !== null) {
                $current = json_decode($this->record->credentials ?? '{}', true);
                $data['credentials'] = json_encode([
                    'api_token' => $newToken ?: ($current['api_token'] ?? null),
                ]);
            }

            $data['extra_settings'] = array_merge($data['extra_settings'] ?? [], [
                'template_username' => $this->data['extra_settings_template_username'] ?? null,
                'sub_base_url' => $this->data['extra_settings_sub_base_url'] ?? null,
            ]);

            return $data;
        }

        $newUsername = $this->data['credentials_username'] ?? null;
        $newPassword = $this->data['credentials_password'] ?? null;

        if ($newUsername === null && $newPassword === null) {
            return $data;
        }

        $current = json_decode($this->record->credentials ?? '{}', true);

        $data['credentials'] = json_encode([
            'username' => $newUsername ?: ($current['username'] ?? null),
            'password' => $newPassword ?: ($current['password'] ?? null),
        ]);

        return $data;
    }
}
