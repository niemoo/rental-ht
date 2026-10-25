<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalItem>
 */
class RentalItemFactory extends Factory
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
            'unit_id' => Unit::factory(),
            'daily_rate' => fn (array $attributes): int => (int) Unit::query()->find($attributes['unit_id'])?->category?->daily_rate,
            'returned_at' => null,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'returned_at' => now(),
        ]);
    }
}
