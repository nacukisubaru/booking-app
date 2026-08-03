<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomOffersController;
use Illuminate\Support\Facades\Route;

Route::post('/room-offers/search', [RoomOffersController::class, 'search']);

Route::post('/bookings', [BookingController::class, 'store']);
