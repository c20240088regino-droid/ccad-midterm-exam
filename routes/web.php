<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentRentalController;
use App\Http\Controllers\HeavyEquipmentController;
use App\Http\Controllers\ProjectSiteController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'dashboard');
    }

    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('admin/dashboard', [DashboardController::class, 'adminDashboard'])
        ->middleware('admin')
        ->name('admin.dashboard');

    Route::resource('contractors', ContractorController::class)
        ->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
    Route::resource('project-sites', ProjectSiteController::class)
        ->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
    Route::get('register-equipment', [HeavyEquipmentController::class, 'registrationPage'])
        ->name('register-equipment');
    Route::resource('heavy-equipment', HeavyEquipmentController::class)
        ->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
    Route::resource('equipment-rentals', EquipmentRentalController::class)
        ->only(['create', 'store']);

    Route::middleware('admin')->group(function () {
        Route::resource('equipment-rentals', EquipmentRentalController::class)
            ->only(['index', 'show', 'update', 'destroy']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
