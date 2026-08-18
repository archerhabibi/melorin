<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => 'کارت به کارت بانک ملی',
            'type' => 'card_to_card',
            'status' => 'active',
            'settings' => [
                'card_number' => '6037-9975-1234-5678',
                'card_holder_name' => 'شرکت ملورین',
                'bank_name' => 'ملی',
            ],
        ];
    }

    public function zarinpal(): static
    {
        return $this->state(fn () => [
            'name' => 'زرین‌پال',
            'type' => 'rial_gateway',
            'settings' => [
                'driver' => 'zarinpal',
                'merchant_id' => str_repeat('a', 36),
                'sandbox' => true,
            ],
        ]);
    }
}
