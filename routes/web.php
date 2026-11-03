<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EquipmentRentalController;
use App\Http\Controllers\HeavyEquipmentController;
use App\Models\Contractor;
use App\Models\EquipmentRental;
use App\Models\HeavyEquipment;
use App\Models\ProjectSite;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard', [
            'stats' => [
                'totalEquipment' => HeavyEquipment::query()->count(),
                'activeRentals' => EquipmentRental::query()
                    ->whereDate('start_date', '<=', now()->toDateString())
                    ->whereDate('end_date', '>=', now()->toDateString())
                    ->count(),
                'available' => HeavyEquipment::query()->where('machine_status', 'Available')->count(),
                'maintenance' => HeavyEquipment::query()->where('machine_status', 'Maintenance')->count(),
            ],
            'rentals' => EquipmentRental::query()
                ->with(['heavyEquipment:id,equipment_tag,machine_model', 'contractor:id,contractor_name', 'projectSite:id,project_site'])
                ->latest()
                ->limit(12)
                ->get(),
            'equipmentOptions' => HeavyEquipment::query()
                ->where('machine_status', '!=', 'Maintenance')
                ->orderBy('equipment_tag')
                ->get(['id', 'equipment_tag', 'machine_model', 'daily_rate']),
            'contractors' => Contractor::query()->orderBy('contractor_name')->get(['id', 'contractor_name']),
            'projectSites' => ProjectSite::query()->orderBy('project_site')->get(['id', 'project_site']),
        ]);
    })->name('dashboard');

    Route::post('contractors', function (\Illuminate\Http\Request $request) {
        $data = $request->validate(['contractor_name' => ['required', 'string', 'max:150']]);
        $contractor = Contractor::query()->firstOrCreate(['contractor_name' => trim($data['contractor_name'])]);

        return response()->json($contractor, 201);
    });

    Route::post('project-sites', function (\Illuminate\Http\Request $request) {
        $data = $request->validate(['project_site' => ['required', 'string', 'max:150']]);
        $site = ProjectSite::query()->firstOrCreate(['project_site' => trim($data['project_site'])]);

        return response()->json($site, 201);
    });

    Route::resource('heavy-equipment', HeavyEquipmentController::class)
        ->only(['index', 'create', 'store', 'update']);
    Route::resource('equipment-rentals', EquipmentRentalController::class)
        ->only(['index', 'create', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
