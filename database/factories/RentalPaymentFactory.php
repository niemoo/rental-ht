<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Rental;
use App\Models\RentalPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPayment>
 */
class RentalPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'amount' => 100000,
            'paid_at' => today(),
            'method' => PaymentMethod::Cash,
        ];
    }
}
