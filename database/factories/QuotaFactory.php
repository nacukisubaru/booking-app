<?php

namespace Database\Factories;

use App\Models\Quota;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quota>
 */
class QuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'room_type_id' => RoomType::factory(),
            'count' => fake()->numberBetween(0, 10),
        ];
    }
}
