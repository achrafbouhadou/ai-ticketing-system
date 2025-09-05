<?php

use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

Route::apiResource('tickets', TicketController::class)->only([
    'index','store','show','update'
]);

Route::post('tickets/{id}/classify', [ClassificationController::class, 'store'])
    ->middleware('throttle:classify');

 Route::get('stats', [StatsController::class, 'index']);
