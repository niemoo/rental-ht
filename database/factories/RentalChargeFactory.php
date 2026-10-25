<?php

namespace Database\Factories;

use App\Enums\ChargeType;
use App\Models\Rental;
use App\Models\RentalCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalCharge>
 */
class RentalChargeFactory extends Factory
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
            'type' => ChargeType::Delivery,
            'amount' => 50000,
        ];
    }
}
