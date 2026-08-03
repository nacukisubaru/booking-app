<?php

namespace App\Services;

use App\DTOs\AvailabilityQueryDto;
use App\Http\Requests\SearchRoomOffersRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class RoomOffersService
{
    /**
     * Search for room offers matching the requested nights and guests.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(SearchRoomOffersRequest $request): array
    {
        $checkIn = $request->date('check_in');
        $checkOut = $request->date('check_out');
        $nights = (int) $checkIn->diffInDays($checkOut);

        $query = $this->buildBaseOffersQuery(
            $checkIn->toDateString(),
            $checkOut->toDateString(),
            $request->integer('adults'),
            $request->integer('childrens'),
            $nights,
        );

        $query
            ->selectRaw('
                h.name AS hotel_name, h.id AS hotel_id,
                r.name AS room_type_name, r.id AS room_type_id,
                tp.name AS tariff_name, tp.id AS tariff_id
            ')
            ->addSelect(DB::raw('(
                SELECT '.$this->servicesAggregate().'
                FROM services s2
                INNER JOIN tariff_plans_services tps2 ON s2.id = tps2.service_id
                WHERE tps2.tariff_plan_id = tp.id
            ) AS services'))
            ->addSelect(DB::raw('SUM(t.price) AS total_room_price'))
            ->selectRaw('SUM(t.price) + ('.$this->servicePriceSubquery().') AS total_price', [$nights]);

        if ($request->filled('services_ids')) {
            $ids = (array) $request->input('services_ids');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $query->whereRaw("EXISTS (
                SELECT 1 FROM tariff_plans_services tps_filter
                WHERE tps_filter.tariff_plan_id = tp.id
                  AND tps_filter.service_id IN ($placeholders)
            )", $ids);
        }

        $query->groupBy(['h.id', 'h.name', 'r.id', 'r.name', 'tp.id', 'tp.name']);

        if ($request->filled('max_price')) {
            $query->havingRaw(
                '(SUM(t.price) + ('.$this->servicePriceSubquery().')) < ?',
                [$nights, $request->integer('max_price')],
            );
        }

        return $query->get()
            ->map(fn (object $row): array => [
                'hotel_name' => $row->hotel_name,
                'hotel_id' => $row->hotel_id,
                'room_type_name' => $row->room_type_name,
                'room_type_id' => $row->room_type_id,
                'tariff_name' => $row->tariff_name,
                'tariff_id' => $row->tariff_id,
                'services' => json_decode((string) ($row->services ?? '[]'), true),
                'total_room_price' => (float) $row->total_room_price,
                'total_price' => (float) $row->total_price,
            ])
            ->all();
    }

    /**
     * Check whether a specific room type + tariff plan combo is available
     * for the requested nights and whether its price matches the expectation.
     *
     * @return array<string, mixed>
     */
    public function checkAvailability(AvailabilityQueryDto $dto): array
    {
        $nights = $dto->nights();

        $query = $this->buildBaseOffersQuery(
            $dto->startDate(),
            $dto->endDate(),
            $dto->adults,
            $dto->childrens,
            $nights,
        );

        $query
            ->where('r.id', $dto->roomTypeId)
            ->where('tp.id', $dto->tariffPlanId)
            ->selectRaw('
                h.id AS hotel_id,
                r.id AS room_type_id,
                tp.id AS tariff_plan_id,
                SUM(t.price) AS total_room_price
            ')
            ->selectRaw('SUM(t.price) + ('.$this->servicePriceSubquery().') AS total_price', [$nights])
            ->groupBy(['h.id', 'r.id', 'tp.id']);

        $result = $query->first();

        if (! $result) {
            return [
                'is_available' => false,
                'price_matches' => false,
                'expected_price' => $dto->expectedPrice,
            ];
        }

        $totalPrice = (float) $result->total_price;
        $priceMatches = round($totalPrice * 100) === round($dto->expectedPrice * 100);

        return [
            'is_available' => true,
            'price_matches' => $priceMatches,
            'expected_price' => $dto->expectedPrice,
            'total_price' => $totalPrice,
            'total_room_price' => (float) $result->total_room_price,
            'hotel_id' => $result->hotel_id,
            'room_type_id' => $result->room_type_id,
            'tariff_plan_id' => $result->tariff_plan_id,
        ];
    }

    /**
     * Build the base offers query: nightly tariffs, nightly quotas,
     * matching occupancy and non-archived hotel/room type.
     */
    private function buildBaseOffersQuery(string $startDate, string $endDate, int $adults, int $childrens, int $countOfNights): Builder
    {
        return DB::table('tariffs as t')
            ->join('tariff_plans as tp', 'tp.id', '=', 't.tariff_plan_id')
            ->join('room_types as r', 'r.id', '=', 't.room_type_id')
            ->join('hotels as h', 'h.id', '=', 'r.hotel_id')
            ->join('occupancies as o', 'o.room_type_id', '=', 'r.id')
            // The check-out day is exclusive: dates range over [start, end).
            ->where('t.date', '>=', $startDate)
            ->where('t.date', '<', $endDate)
            ->where('o.adults', $adults)
            ->where('o.childrens', $childrens)
            ->where('r.is_archive', false)
            ->where('h.is_archive', false)
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM quotas q
                WHERE q.room_type_id = r.id
                  AND q.date >= ? AND q.date < ?
                  AND q.count < 1
            )', [$startDate, $endDate])
            ->whereRaw('(SELECT COUNT(*) FROM tariffs t2
                WHERE t2.room_type_id = r.id AND t2.tariff_plan_id = tp.id
                  AND t2.date >= ? AND t2.date < ?
                  AND t2.price > 0
            ) = ?', [$startDate, $endDate, $countOfNights]);
    }

    /**
     * The per-night price of the services included in the tariff plan.
     * The night count is passed as a bound parameter (?).
     *
     * @return literal-string
     */
    private function servicePriceSubquery(): string
    {
        return 'COALESCE((
            SELECT SUM(s.price)
            FROM services s
            INNER JOIN tariff_plans_services tps ON s.id = tps.service_id
            WHERE tps.tariff_plan_id = tp.id
        ), 0) * ?';
    }

    /**
     * PostgreSQL uses json_agg; SQLite (used by the test suite) uses json_group_array.
     *
     * @return literal-string
     */
    private function servicesAggregate(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'json_agg(s2.name)'
            : 'json_group_array(s2.name)';
    }
}
