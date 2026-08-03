<?php

namespace Database\Factories;

use App\Models\RoomType;
use App\Models\Tariff;
use App\Models\TariffPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tariff>
 */
class TariffFactory extends Factory
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
            'tariff_plan_id' => TariffPlan::factory(),
            'price' => fake()->randomFloat(2, 50, 1000),
        ];
    }
}
