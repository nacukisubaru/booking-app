<?php

namespace Database\Factories;

use App\Models\Occupancy;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Occupancy>
 */
class OccupancyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'adults' => fake()->numberBetween(1, 2),
            'childrens' => fake()->numberBetween(0, 1),
            'extra_bed' => false,
        ];
    }
}
