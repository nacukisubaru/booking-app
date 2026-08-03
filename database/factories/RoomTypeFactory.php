<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'hotel_id' => Hotel::factory(),
            'max_adults' => fake()->numberBetween(1, 4),
            'max_childrens' => fake()->numberBetween(0, 2),
            'total_guests' => fn (array $attributes): int => $attributes['max_adults'] + $attributes['max_childrens'],
            'is_archive' => false,
        ];
    }
}
