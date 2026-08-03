<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\TariffPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TariffPlan>
 */
class TariffPlanFactory extends Factory
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
        ];
    }
}
