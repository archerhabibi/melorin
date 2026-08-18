<?php

namespace Database\Factories;

use App\Models\Protocol;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProtocolFactory extends Factory
{
    protected $model = Protocol::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['vless', 'vmess', 'trojan']),
            'status' => 'active',
        ];
    }
}
