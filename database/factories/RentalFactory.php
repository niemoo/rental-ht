<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * Define the model's default state: out now, due in a few days.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'start_date' => now()->subDay()->startOfDay(),
            'due_date' => now()->addDays(3)->endOfDay(),
        ];
    }

    /**
     * Rental spanning the given day offsets relative to today.
     */
    public function between(int $startOffset, int $dueOffset): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->addDays($startOffset)->startOfDay(),
            'due_date' => now()->addDays($dueOffset)->endOfDay(),
        ]);
    }

    /**
     * Attach the given units (or one fresh unit) as unreturned items.
     *
     * @param  iterable<Unit>|Unit|null  $units
     */
    public function withUnits(iterable|Unit|null $units = null): static
    {
        return $this->afterCreating(function (Rental $rental) use ($units): void {
            $units = match (true) {
                $units === null => [Unit::factory()->create()],
                $units instanceof Unit => [$units],
                default => $units,
            };

            foreach ($units as $unit) {
                RentalItem::factory()->for($rental)->for($unit)->create();
            }

            $rental->syncTotals();
        });
    }

    /**
     * Mark every item as returned on the due date.
     */
    public function returned(): static
    {
        return $this->afterCreating(function (Rental $rental): void {
            $rental->items()->update(['returned_at' => $rental->due_date]);
        });
    }
}
