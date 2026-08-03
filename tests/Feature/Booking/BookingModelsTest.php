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
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_uses_uuid_primary_key(): void
    {
        $hotel = Hotel::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $hotel->id
        );
    }

    public function test_hotel_has_default_check_in_and_check_out_times_and_is_not_archived(): void
    {
        $hotel = Hotel::factory()->create(['description' => null]);

        $this->assertFalse($hotel->is_archive);
        $this->assertSame('14:00:00', $hotel->check_in_time);
        $this->assertSame('12:00:00', $hotel->check_out_time);
        $this->assertNull($hotel->description);
    }

    public function test_hotel_has_many_room_types_tariff_plans_and_services(): void
    {
        $hotel = Hotel::factory()->create();

        $roomType = RoomType::factory()->for($hotel)->create();
        $tariffPlan = TariffPlan::factory()->for($hotel)->create();
        $service = Service::factory()->for($hotel)->create();

        $this->assertTrue($hotel->roomTypes->contains($roomType));
        $this->assertTrue($hotel->tariffPlans->contains($tariffPlan));
        $this->assertTrue($hotel->services->contains($service));
    }

    public function test_room_type_belongs_to_hotel_and_has_many_children(): void
    {
        $roomType = RoomType::factory()->create(['is_archive' => 1]);

        $occupancy = Occupancy::factory()->create(['room_type_id' => $roomType->id]);
        $quota = Quota::factory()->create(['room_type_id' => $roomType->id]);
        $tariff = Tariff::factory()->create(['room_type_id' => $roomType->id]);
        $order = Order::factory()->create(['roomTypeId' => $roomType->id]);

        $this->assertTrue($roomType->is_archive === true);
        $this->assertSame($roomType->hotel_id, $roomType->hotel->id);
        $this->assertTrue($roomType->occupancies->contains($occupancy));
        $this->assertTrue($roomType->quotas->contains($quota));
        $this->assertTrue($roomType->tariffs->contains($tariff));
        $this->assertTrue($roomType->orders->contains($order));
    }

    public function test_occupancy_belongs_to_room_type_and_casts_extra_bed_to_bool(): void
    {
        $occupancy = Occupancy::factory()->create(['extra_bed' => 1]);

        $this->assertTrue($occupancy->extra_bed === true);
        $this->assertSame($occupancy->room_type_id, $occupancy->roomType->id);
    }

    public function test_tariff_plan_services_sum_returns_total_price_of_attached_services(): void
    {
        $tariffPlan = TariffPlan::factory()->create();

        $serviceA = Service::factory()->create(['price' => 100.50]);
        $serviceB = Service::factory()->create(['price' => 49.50]);

        $tariffPlan->services()->attach([$serviceA->id, $serviceB->id]);

        $this->assertSame(150.0, $tariffPlan->services_sum);
        $this->assertSame(2, $tariffPlan->services->count());
        $this->assertTrue($serviceA->tariffPlans->contains($tariffPlan));
    }

    public function test_service_belongs_to_many_tariff_plans_through_pivot(): void
    {
        $service = Service::factory()->create();
        $plan = TariffPlan::factory()->create();

        $service->tariffPlans()->attach($plan->id);

        $this->assertTrue($service->tariffPlans->contains($plan));
        $this->assertDatabaseHas('tariff_plans_services', [
            'tariff_plan_id' => $plan->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_pivot_unique_constraint_prevents_duplicate_attachments(): void
    {
        $service = Service::factory()->create();
        $plan = TariffPlan::factory()->create();

        $plan->services()->attach($service->id);

        $this->expectException(QueryException::class);

        $plan->services()->attach($service->id);
    }

    public function test_tariff_uses_auto_increment_primary_key_and_casts_date(): void
    {
        $tariff = Tariff::factory()->create(['date' => '2026-08-01', 'price' => 123.45]);

        $this->assertIsInt($tariff->id);
        $this->assertInstanceOf(CarbonInterface::class, $tariff->date);
        $this->assertSame('2026-08-01', $tariff->date->toDateString());
        $this->assertInstanceOf(RoomType::class, $tariff->roomType);
        $this->assertInstanceOf(TariffPlan::class, $tariff->tariffPlan);
    }

    public function test_quota_uses_auto_increment_primary_key_and_casts_date(): void
    {
        $quota = Quota::factory()->create(['date' => '2026-08-01', 'count' => 3]);

        $this->assertIsInt($quota->id);
        $this->assertInstanceOf(CarbonInterface::class, $quota->date);
        $this->assertSame('2026-08-01', $quota->date->toDateString());
        $this->assertSame(3, $quota->count);
        $this->assertInstanceOf(RoomType::class, $quota->roomType);
    }

    public function test_order_uses_uuid_defaults_to_pending_and_auto_fills_created_at(): void
    {
        $order = Order::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $order->id
        );
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertInstanceOf(CarbonInterface::class, $order->created_at);
        $this->assertNull($order->updated_at);
        $this->assertIsFloat($order->total_price);
        $this->assertInstanceOf(RoomType::class, $order->roomType);
    }

    public function test_order_daily_prices_are_persisted_and_come_back_as_array(): void
    {
        $prices = [
            '2026-08-10' => 120.5,
            '2026-08-11' => 120.5,
        ];

        $order = Order::factory()->create(['daily_prices' => $prices]);

        $this->assertSame($prices, $order->daily_prices);
        $this->assertSame($prices, $order->fresh()->daily_prices);
    }

    public function test_order_status_can_be_transitioned(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $this->assertSame(OrderStatus::Confirmed, $order->status);

        $order->update(['status' => OrderStatus::Cancelled]);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status->value);
    }

    public function test_order_status_rejects_unknown_value(): void
    {
        $this->expectException(\ValueError::class);

        Order::factory()->create(['status' => 'invalid']);
    }

    public function test_order_status_enum_exposes_expected_values(): void
    {
        $this->assertSame('pending', OrderStatus::Pending->value);
        $this->assertSame('confirmed', OrderStatus::Confirmed->value);
        $this->assertSame('cancelled', OrderStatus::Cancelled->value);
    }
}
