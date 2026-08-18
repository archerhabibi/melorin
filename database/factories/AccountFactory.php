<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Order;
use App\Models\Product;
use App\Models\ServerPanel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'server_panel_id' => ServerPanel::factory(),
            'panel_username' => 'melorin_' . Str::lower(Str::random(8)),
            'config_data' => json_encode(['note' => 'test']),
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
            'is_test' => false,
        ];
    }
}
