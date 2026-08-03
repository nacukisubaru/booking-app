<?php

use App\Http\Controllers\RoomOffersController;
use Illuminate\Support\Facades\Route;

Route::post('/room-offers/search', [RoomOffersController::class, 'search']);
Route::post('/room-offers/availability', [RoomOffersController::class, 'checkAvailability']);
