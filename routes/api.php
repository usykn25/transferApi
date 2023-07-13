<?php

use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PassengerController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::resource('cars', CarController::class);
Route::resource('drivers', DriverController::class);
Route::resource('passengers', PassengerController::class);
Route::resource('transfers', TransferController::class);
