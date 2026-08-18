<?php

namespace App\Filament\Resources\ServerPanelResource\Pages;

use App\Filament\Resources\ServerPanelResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * فیلدهای credentials_* و extra_settings_template_username فقط UI هستند
 * (در مدل چنین ستون‌هایی وجود ندارد)؛ اینجا آن‌ها را به فیلدهای واقعی
 * credentials (رمزنگاری‌شده) و extra_settings تبدیل می‌کنیم.
 *
 * شکل credentials بسته به panel_type فرق می‌کند:
 *   - marzban / pasarguard: {"username": ..., "password": ...}
 *   - sanaei:                {"api_token": ...}
 */
class CreateServerPanel extends CreateRecord
{
    protected static string $resource = ServerPanelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['panel_type'] ?? null) === 'sanaei') {
            $data['credentials'] = json_encode([
                'api_token' => $this->data['credentials_api_token'] ?? null,
            ]);

            $data['extra_settings'] = array_merge($data['extra_settings'] ?? [], [
                'template_username' => $this->data['extra_settings_template_username'] ?? null,
                'sub_base_url' => $this->data['extra_settings_sub_base_url'] ?? null,
            ]);
        } else {
            $data['credentials'] = json_encode([
                'username' => $this->data['credentials_username'] ?? null,
                'password' => $this->data['credentials_password'] ?? null,
            ]);
        }

        return $data;
    }
}
