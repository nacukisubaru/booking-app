<?php

namespace Tests\Feature\Booking;

use App\Models\Hotel;
use App\Models\Occupancy;
use App\Models\Quota;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Tariff;
use App\Models\TariffPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RoomOffersApiTest extends TestCase
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

    public function test_search_returns_combo_with_complete_nightly_tariffs(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $response = $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
        ]);

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.hotel_id', $combo['hotel']->id)
            ->assertJsonPath('0.room_type_id', $combo['roomType']->id)
            ->assertJsonPath('0.tariff_id', $combo['plan']->id)
            ->assertJsonPath('0.total_room_price', 300)
            ->assertJsonPath('0.total_price', 300);
    }

    public function test_search_excludes_combo_missing_a_nightly_tariff(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-12', $combo['roomType'], $combo['plan']); // только 2 ночи из 3

        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
        ])->assertOk()->assertJsonCount(0);
    }

    public function test_search_excludes_combo_with_zero_quota_on_any_night(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan'], quotaCount: 0);

        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
        ])->assertOk()->assertJsonCount(0);
    }

    public function test_search_excludes_archived_hotel_and_room_type(): void
    {
        $hotel = Hotel::factory()->create(['is_archive' => true]);
        $roomType = RoomType::factory()->create([
            'hotel_id' => $hotel->id,
            'max_adults' => 2,
            'max_childrens' => 1,
            'total_guests' => 3,
        ]);
        Occupancy::factory()->create(['room_type_id' => $roomType->id, 'adults' => 2, 'childrens' => 1]);
        $plan = TariffPlan::factory()->create(['hotel_id' => $hotel->id]);
        $this->seedNights('2026-08-10', '2026-08-13', $roomType, $plan);

        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
        ])->assertOk()->assertJsonCount(0);

        $roomType->update(['is_archive' => true]);
        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
        ])->assertOk()->assertJsonCount(0);
    }

    public function test_search_returns_services_as_json_array_and_includes_them_in_total_price(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $service = Service::factory()->create(['hotel_id' => $combo['hotel']->id, 'price' => 50.0]);
        $combo['plan']->services()->attach($service->id);

        $response = $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('0.services', [$service->name])
            ->assertJsonPath('0.total_room_price', 300)
            ->assertJsonPath('0.total_price', 450); // 300 + 50 * 3 ночи
    }

    public function test_search_filters_by_services_ids(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $breakfast = Service::factory()->create(['hotel_id' => $combo['hotel']->id, 'price' => 50.0]);
        $parking = Service::factory()->create(['hotel_id' => $combo['hotel']->id, 'price' => 20.0]);
        $combo['plan']->services()->attach([$breakfast->id, $parking->id]);

        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'services_ids' => [$breakfast->id],
        ])->assertOk()->assertJsonCount(1);

        $otherPlan = TariffPlan::factory()->create(['hotel_id' => $combo['hotel']->id]);
        $otherPlan->services()->attach($parking->id);
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $otherPlan);

        // План без искомой услуги не попадает в выдачу.
        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'services_ids' => [$breakfast->id],
        ])->assertOk()->assertJsonCount(1);
    }

    public function test_search_max_price_filters_the_full_price_including_services(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $service = Service::factory()->create(['hotel_id' => $combo['hotel']->id, 'price' => 50.0]);
        $combo['plan']->services()->attach($service->id);

        // Полная цена = 450, значит 400 отсекает комбо, а 500 — пропускает.
        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'max_price' => 400,
        ])->assertOk()->assertJsonCount(0);

        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'max_price' => 500,
        ])->assertOk()->assertJsonCount(1);
    }

    public function test_check_availability_returns_true_when_price_matches(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan'], price: 100.0);

        $this->postJson('/api/room-offers/availability', [
            'room_type_id' => $combo['roomType']->id,
            'tariff_plan_id' => $combo['plan']->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'expected_price' => 300,
        ])->assertOk()
            ->assertJsonPath('is_available', true)
            ->assertJsonPath('price_matches', true)
            ->assertJsonPath('total_price', 300)
            ->assertJsonPath('hotel_id', $combo['hotel']->id);
    }

    public function test_check_availability_returns_available_but_price_mismatch(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan'], price: 100.0);

        $this->postJson('/api/room-offers/availability', [
            'room_type_id' => $combo['roomType']->id,
            'tariff_plan_id' => $combo['plan']->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'expected_price' => 999,
        ])->assertOk()
            ->assertJsonPath('is_available', true)
            ->assertJsonPath('price_matches', false);
    }

    public function test_check_availability_returns_not_available_for_unknown_combo(): void
    {
        $combo = $this->createAvailableCombo();
        $this->seedNights('2026-08-10', '2026-08-13', $combo['roomType'], $combo['plan']);

        $otherPlan = TariffPlan::factory()->create(['hotel_id' => $combo['hotel']->id]);

        $this->postJson('/api/room-offers/availability', [
            'room_type_id' => $combo['roomType']->id,
            'tariff_plan_id' => $otherPlan->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'expected_price' => 300,
        ])->assertOk()
            ->assertJsonPath('is_available', false)
            ->assertJsonPath('price_matches', false);
    }

    public function test_inverted_dates_return_422_with_validation_error(): void
    {
        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-13',
            'check_out' => '2026-08-10',
            'adults' => 2,
            'childrens' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'validation_error');
    }

    public function test_invalid_date_format_returns_422_with_validation_error(): void
    {
        $this->postJson('/api/room-offers/search', [
            'check_in' => '13-08-2026',
            'check_out' => '2026-08-15',
            'adults' => 2,
            'childrens' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'validation_error');
    }

    public function test_missing_required_fields_return_422_with_validation_error(): void
    {
        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonValidationErrors(['adults', 'childrens']);
    }

    public function test_invalid_services_ids_return_422(): void
    {
        $this->postJson('/api/room-offers/search', [
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'services_ids' => ['not-a-uuid'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['services_ids.0']);
    }

    public function test_unknown_room_type_in_availability_returns_422(): void
    {
        $this->postJson('/api/room-offers/availability', [
            'room_type_id' => '00000000-0000-0000-0000-000000000000',
            'tariff_plan_id' => '00000000-0000-0000-0000-000000000000',
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'adults' => 2,
            'childrens' => 1,
            'expected_price' => 300,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'validation_error');
    }
}
