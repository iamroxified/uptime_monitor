<?php

use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\MonitorHistoryController;
use Illuminate\Support\Facades\Route;

Route::post('/monitors', [MonitorController::class, 'store']);
Route::get('/monitors', [MonitorController::class, 'index']);
Route::get('/monitors/{monitor}/history', [MonitorHistoryController::class, 'index'])
    ->missing(fn () => response()->json(['message' => 'Monitor not found.'], 404));

