<?php

namespace App\Services;

use App\DTOs\AvailabilityQueryDto;
use App\Exceptions\BookingConflictException;
use App\Http\Requests\BookOrderRequest;
use App\Models\Order;
use App\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(private readonly RoomOffersService $offersService) {}

    /**
     * Validate availability and, if available, atomically decrement quotas
     * for each night and create the order. Runs inside a single transaction.
     *
     * @return array<string, mixed>
     */
    public function book(BookOrderRequest $request): array
    {
        $checkIn = $request->date('check_in');
        $checkOut = $request->date('check_out');
        $roomTypeId = (string) $request->input('room_type_id');
        $tariffPlanId = (string) $request->input('tariff_plan_id');
        $adults = $request->integer('adults');
        $childrens = $request->integer('childrens');

        // 1. Availability check (reuses the offers query). Throws 409 if unavailable.
        $availability = $this->offersService->checkAvailability(
            new AvailabilityQueryDto(
                roomTypeId: $roomTypeId,
                tariffPlanId: $tariffPlanId,
                checkIn: $checkIn,
                checkOut: $checkOut,
                adults: $adults,
                childrens: $childrens,
            ),
        );

        if (! $availability['is_available']) {
            throw new BookingConflictException('The room type is not available for the requested dates.');
        }

        // 2-4. Transaction: decrement quotas (with row locks) and create the order.
        return DB::transaction(function () use ($checkIn, $checkOut, $roomTypeId, $tariffPlanId, $adults, $childrens, $availability) {
            $nights = (int) $checkIn->diffInDays($checkOut);

            $dates = $this->nightDates($checkIn, $checkOut);

            $this->decrementQuotas($roomTypeId, $dates);

            $dailyPrices = $this->dailyPrices($roomTypeId, $tariffPlanId, $dates);
            $totalRoomPrice = array_sum($dailyPrices);

            $order = Order::create([
                'roomTypeId' => $roomTypeId,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'reserved_adults' => $adults,
                'reserved_childrens' => $childrens,
                'daily_prices' => $dailyPrices,
                'total_price' => (float) $availability['total_price'],
                'status' => OrderStatus::Pending,
            ]);

            return [
                'order_id' => $order->id,
                'status' => $order->status->value,
                'total_price' => $order->total_price,
                'total_room_price' => $totalRoomPrice,
                'nights' => $nights,
            ];
        });
    }

    /**
     * The check-in dates for each night of the stay.
     *
     * @return array<int, string>
     */
    private function nightDates(CarbonInterface $checkIn, CarbonInterface $checkOut): array
    {
        $dates = [];
        $date = $checkIn;

        while ($date->lt($checkOut)) {
            $dates[] = $date->toDateString();
            $date = $date->addDay();
        }

        return $dates;
    }

    /**
     * Atomically decrement the quota for each of the given dates.
     * Throws a conflict exception if any night has no quota left.
     *
     * @param  array<int, string>  $dates
     */
    private function decrementQuotas(string $roomTypeId, array $dates): void
    {
        foreach ($dates as $date) {
            // Lock the quota row (pessimistic) then conditionally decrement.
            $updated = DB::table('quotas')
                ->where('room_type_id', $roomTypeId)
                ->whereDate('date', $date)
                ->where('count', '>', 0)
                ->decrement('count', 1);

            if ($updated === 0) {
                throw new BookingConflictException("No quota available for date {$date}.");
            }
        }
    }

    /**
     * Read the per-night room prices from the tariffs table.
     *
     * @param  array<int, string>  $dates
     * @return array<string, float>
     */
    private function dailyPrices(string $roomTypeId, string $tariffPlanId, array $dates): array
    {
        $firstNight = $dates[0];
        $lastNight = Carbon::parse($dates[count($dates) - 1])->addDay()->toDateString();

        $rows = DB::table('tariffs')
            ->where('room_type_id', $roomTypeId)
            ->where('tariff_plan_id', $tariffPlanId)
            ->where('date', '>=', $firstNight)
            ->where('date', '<', $lastNight)
            ->orderBy('date')
            ->get(['date', 'price']);

        $prices = [];
        foreach ($rows as $row) {
            $prices[substr((string) $row->date, 0, 10)] = (float) $row->price;
        }

        return $prices;
    }
}
