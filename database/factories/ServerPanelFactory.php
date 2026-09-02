<?php

namespace Database\Factories;

use App\Models\ServerPanel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerPanelFactory extends Factory
{
    protected $model = ServerPanel::class;

    public function definition(): array
    {
        return [
            'name' => 'Server '.$this->faker->unique()->city(),
            'panel_type' => 'marzban',
            'host' => 'https://panel.example.com',
            'port' => 8000,
            'credentials' => json_encode(['username' => 'admin', 'password' => 'secret']),
            'status' => 'active',
            'active_accounts_count' => 0,
        ];
    }
}
