<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

Route::apiResource('tickets', TicketController::class)->only([
    'index','store','show','update'
]);
