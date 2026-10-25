<?php

namespace Database\Factories;

use App\Enums\UnitCondition;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'code' => fake()->unique()->bothify('?-###'),
            'condition' => UnitCondition::Good,
        ];
    }

    /**
     * A unit in the given condition, e.g. UnitCondition::Damaged.
     */
    public function condition(UnitCondition $condition): static
    {
        return $this->state(fn (array $attributes) => ['condition' => $condition]);
    }
}
