<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParcoursController;
use App\Http\Controllers\PlanificationController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/suivi', [TrackingController::class, 'index'])->name('tracking');
Route::get('/parcours', [ParcoursController::class, 'index'])->name('parcours');
Route::get('/planification', [PlanificationController::class, 'index'])->name('planification');
Route::get('/portuaire', [PortController::class, 'index'])->name('port');
Route::get('/fluidite', [RoutingController::class, 'index'])->name('routing');
