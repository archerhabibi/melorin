<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => $this->faker->sentence(4),
            'status' => 'open',
            'priority' => 'normal',
        ];
    }
}
