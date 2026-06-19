<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\RoutingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/portuaire', [PortController::class, 'index'])->name('port');
Route::get('/fluidite', [RoutingController::class, 'index'])->name('routing');
