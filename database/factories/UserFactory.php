<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'telegram_id' => $this->faker->unique()->numberBetween(100000, 999999999),
            'full_name' => $this->faker->name(),
            'status' => 'active',
            'joined_from' => 'bot',
        ];
    }
}
