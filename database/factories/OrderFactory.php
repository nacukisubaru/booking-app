<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\RoomType;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'roomTypeId' => RoomType::factory(),
            'check_in_date' => now()->addDay(),
            'check_out_date' => now()->addDays(3),
            'reserved_adults' => 2,
            'reserved_childrens' => 1,
            'daily_prices' => [
                now()->addDay()->toDateString() => 100.0,
                now()->addDays(2)->toDateString() => 150.0,
            ],
            'total_price' => 250.0,
            'status' => OrderStatus::Pending,
        ];
    }
}
