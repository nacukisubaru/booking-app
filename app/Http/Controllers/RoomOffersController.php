<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\SearchRoomOffersRequest;
use App\Services\RoomOffersService;
use Illuminate\Http\JsonResponse;

class RoomOffersController extends Controller
{
    public function __construct(private readonly RoomOffersService $offersService) {}

    /**
     * Search for room offers.
     */
    public function search(SearchRoomOffersRequest $request): JsonResponse
    {
        return response()->json($this->offersService->search($request));
    }

    /**
     * Check the availability of a specific room type + tariff plan combo.
     */
    public function checkAvailability(CheckAvailabilityRequest $request): JsonResponse
    {
        return response()->json($this->offersService->checkAvailability($request));
    }
}
