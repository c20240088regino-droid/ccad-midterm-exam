<?php

namespace App\Services;

use App\Models\Contractor;
use App\Models\EquipmentRental;
use App\Models\HeavyEquipment;
use App\Models\ProjectSite;

class DashboardService
{
    public function data(bool $includeAllRentals = false): array
    {
        $data = [
            'stats' => [
                'totalEquipment' => HeavyEquipment::query()->count(),
                'activeRentals' => EquipmentRental::query()
                    ->whereDate('start_date', '<=', now()->toDateString())
                    ->whereDate('end_date', '>=', now()->toDateString())
                    ->count(),
                'available' => HeavyEquipment::query()->where('machine_status', 'Available')->count(),
                'maintenance' => HeavyEquipment::query()->where('machine_status', 'Maintenance')->count(),
            ],
            'equipmentOptions' => HeavyEquipment::query()
                ->orderBy('equipment_tag')
                ->get(['id', 'equipment_tag', 'machine_model', 'daily_rate', 'machine_status']),
            'contractors' => Contractor::query()
                ->orderBy('contractor_name')
                ->get(['id', 'contractor_name']),
            'projectSites' => ProjectSite::query()
                ->orderBy('project_site')
                ->get(['id', 'project_site']),
        ];

        if ($includeAllRentals) {
            $data['rentals'] = EquipmentRental::query()
                ->with([
                    'heavyEquipment:id,equipment_tag,machine_model',
                    'contractor:id,contractor_name',
                    'projectSite:id,project_site',
                ])
                ->latest()
                ->get();
        }

        return $data;
    }
}
