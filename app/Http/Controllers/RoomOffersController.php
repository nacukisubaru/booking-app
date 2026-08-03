<?php

namespace App\Http\Controllers;

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
}
