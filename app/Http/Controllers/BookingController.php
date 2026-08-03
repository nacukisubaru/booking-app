<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookOrderRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    /**
     * Create a booking for the requested room type and dates.
     */
    public function store(BookOrderRequest $request): JsonResponse
    {
        $booking = $this->bookingService->book($request);

        return response()->json($booking, 201);
    }
}
