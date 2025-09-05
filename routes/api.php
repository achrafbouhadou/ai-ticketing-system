<?php

use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketExportController;

Route::apiResource('tickets', TicketController::class)->only([
    'index','store','show','update'
]);

Route::post('tickets/{id}/classify', [ClassificationController::class, 'store'])
    ->middleware('throttle:classify');

 Route::get('stats', [StatsController::class, 'index']);

Route::post('tickets/export', [TicketExportController::class, 'store']);
Route::get('tickets/export/{id}', [TicketExportController::class, 'show']);
Route::get('tickets/export/{id}/download', [TicketExportController::class, 'download'])
    ->name('tickets.export.download');

