<?php

namespace Tests\Feature\Booking;

use App\Models\Hotel;
use App\Models\Occupancy;
use App\Models\Order;
use App\Models\Quota;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Tariff;
use App\Models\TariffPlan;
use App\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private function createAvailableCombo(array $overrides = []): array
    {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->create([
            'hotel_id' => $hotel->id,
            'max_adults' => 2,
            'max_childrens' => 1,
            'total_guests' => 3,
        ]);
        Occupancy::factory()->create([
            'room_type_id' => $roomType->id,
            'adults' => 2,
            'childrens' => 1,
        ]);
        $plan = TariffPlan::factory()->create(['hotel_id' => $hotel->id]);

        return array_merge([
            'hotel' => $hotel,
            'roomType' => $roomType,
            'plan' => $plan,
        ], $overrides);
    }

    private function seedNights(string $checkIn, string $checkOut, RoomType $roomType, TariffPlan $plan, float $price = 100.0, int $quotaCount = 2): void
    {
        $date = Carbon::parse($checkIn);

        while ($date->lt(Carbon::parse($checkOut))) {
            Tariff::factory()->create([
                'room_type_id' => $roomType->id,
                'tariff_plan_id' => $plan->id,
                'date' => $date->toDateString(),
                'price' => $price,
            ]);
            Quota::factory()->create([
                'room_type_id' => $roomType->id,
                'date' => $date->toDateString(),
                'count' => $quotaCount,
            ]);
            $date->addDay();
        }
    }

    private function bookingPayload(array $combo, array $overrides = []): array
    {
        return array_merge([
            'room_type_id' => $combo['roomType']->id,
            'tariff_plan_id' => $combo['plan']->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
        ], $overrides);
    }

    public function test_booking_creates_order_and_decrements_quota(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan'], price: 100.0, quotaCount: 2);

        $response = $this->postJson('/api/bookings', $this->bookingPayload($combo));

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('total_price', 300)
            ->assertJsonPath('total_room_price', 300)
            ->assertJsonPath('nights', 3);

        $this->assertDatabaseHas('orders', [
            'roomTypeId' => $combo['roomType']->id,
            'status' => 'pending',
        ]);

        // Quota decremented by 1 for each night.
        $quotas = Quota::where('room_type_id', $combo['roomType']->id)->get();
        $this->assertCount(3, $quotas);
        foreach ($quotas as $quota) {
            $this->assertSame(1, $quota->count); // 2 - 1
        }

        // daily_prices persisted as a date => price map.
        $order = Order::first();
        $this->assertSame([
            '2026-08-10' => 100,
            '2026-08-11' => 100,
            '2026-08-12' => 100,
        ], $order->daily_prices);
    }

    public function test_booking_includes_services_in_total_price(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan'], price: 100.0);

        $service = Service::factory()->create(['hotel_id' => $combo['hotel']->id, 'price' => 50.0]);
        $combo['plan']->services()->attach($service->id);

        $this->postJson('/api/bookings', $this->bookingPayload($combo))
            ->assertStatus(201)
            ->assertJsonPath('total_price', 450); // 300 (комната) + 50*3 (услуги)
    }

    public function test_booking_returns_conflict_when_quota_is_zero(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan'], quotaCount: 0);

        $this->postJson('/api/bookings', $this->bookingPayload($combo))
            ->assertStatus(409)
            ->assertJsonPath('error', 'conflict_error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_booking_conflict_rolls_back_quota_decrements(): void
    {
        $combo = $this->createAvailableCombo();
        // Quota available on nights 1-2, zero on night 3.
        $this->seedNights('2026-08-10', '2026-08-12', $combo['roomType'], $combo['plan'], quotaCount: 2);
        $this->seedNights('2026-08-12', '2026-08-13', $combo['roomType'], $combo['plan'], quotaCount: 0);

        $this->postJson('/api/bookings', $this->bookingPayload($combo))
            ->assertStatus(409);

        // No order created and the quotas were not touched.
        $this->assertDatabaseCount('orders', 0);

        $quotas = Quota::where('room_type_id', $combo['roomType']->id)
            ->orderBy('date')
            ->get();
        $this->assertCount(3, $quotas);

        // 10th and 11th still have 2; the 12th was 0 from the start.
        $this->assertSame(2, $quotas[0]->count);
        $this->assertSame(2, $quotas[1]->count);
        $this->assertSame(0, $quotas[2]->count);
    }

    public function test_booking_uses_the_last_quota_exactly(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan'], quotaCount: 1);

        $this->postJson('/api/bookings', $this->bookingPayload($combo))
            ->assertStatus(201);

        // Quota now zero for all nights.
        $quotas = Quota::where('room_type_id', $combo['roomType']->id)->get();
        foreach ($quotas as $quota) {
            $this->assertSame(0, $quota->count);
        }

        // Second booking must conflict.
        $this->postJson('/api/bookings', $this->bookingPayload($combo))
            ->assertStatus(409);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_booking_rejects_invalid_dates_with_422(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $this->postJson('/api/bookings', $this->bookingPayload($combo, [
            'check_in' => '2026-08-13',
            'check_out' => '2026-08-10',
        ]))->assertStatus(422)
            ->assertJsonPath('error', 'validation_error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_booking_rejects_unknown_room_type_with_422(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $this->postJson('/api/bookings', $this->bookingPayload($combo, [
            'room_type_id' => '00000000-0000-0000-0000-000000000000',
        ]))->assertStatus(422)
            ->assertJsonPath('error', 'validation_error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_booking_rejects_wrong_occupancy_with_conflict(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        // Occupancy is adults=2, childrens=1; requesting different guests.
        $this->postJson('/api/bookings', $this->bookingPayload($combo, [
            'adults' => 3,
            'childrens' => 0,
        ]))->assertStatus(409)
            ->assertJsonPath('error', 'conflict_error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_booking_status_defaults_to_pending(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $this->postJson('/api/bookings', $this->bookingPayload($combo))->assertStatus(201);

        $this->assertSame(OrderStatus::Pending, Order::first()->status);
    }
}
