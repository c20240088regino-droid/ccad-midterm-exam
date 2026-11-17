<?php

namespace App\Services;

use App\Models\Contractor;
use App\Models\EquipmentRental;
use App\Models\HeavyEquipment;
use App\Models\ProjectSite;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EquipmentRentalService
{
    public function listRentals(int $perPage = 15): LengthAwarePaginator
    {
        return EquipmentRental::query()
            ->with(['heavyEquipment', 'contractor', 'projectSite'])
            ->latest()
            ->paginate($perPage);
    }

    public function rentalFormOptions(): array
    {
        return [
            'equipment' => HeavyEquipment::query()
                ->where('machine_status', '!=', 'Maintenance')
                ->orderBy('equipment_tag')
                ->get(),
            'contractors' => Contractor::query()->orderBy('contractor_name')->get(),
            'project_sites' => ProjectSite::query()->orderBy('project_site')->get(),
        ];
    }

    public function findRental(int $id): EquipmentRental
    {
        return EquipmentRental::query()
            ->with(['heavyEquipment', 'contractor', 'projectSite'])
            ->findOrFail($id);
    }

    public function createRental(array $data): EquipmentRental
    {
        return DB::transaction(function () use ($data): EquipmentRental {
            $equipment = HeavyEquipment::query()
                ->lockForUpdate()
                ->findOrFail($data['heavy_equipment_id']);

            if ($equipment->machine_status === 'Maintenance') {
                throw ValidationException::withMessages([
                    'heavy_equipment_id' => 'Equipment under maintenance cannot be rented.',
                ]);
            }

            $data = $this->normalizeRentalData($data, $equipment);
            $this->assertNoDateConflict($data);
            $rental = EquipmentRental::create($data);
            $this->refreshMachineStatus($equipment);

            return $rental->load(['heavyEquipment', 'contractor', 'projectSite']);
        });
    }

    public function updateRental(int $id, array $data): EquipmentRental
    {
        return DB::transaction(function () use ($id, $data): EquipmentRental {
            $rental = EquipmentRental::query()->lockForUpdate()->findOrFail($id);
            $oldEquipmentId = (int) $rental->heavy_equipment_id;
            $data = array_replace($rental->only([
                'heavy_equipment_id', 'contractor_id', 'project_site_id', 'start_date', 'end_date',
                'daily_rate', 'fuel_liters_used', 'fuel_rate',
            ]), $data);
            $equipmentIds = array_values(array_unique([$oldEquipmentId, (int) $data['heavy_equipment_id']]));
            sort($equipmentIds);
            $equipment = HeavyEquipment::query()->whereIn('id', $equipmentIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if (! $equipment->has((int) $data['heavy_equipment_id'])) {
                throw (new ModelNotFoundException())->setModel(HeavyEquipment::class, [$data['heavy_equipment_id']]);
            }

            $targetEquipment = $equipment->get((int) $data['heavy_equipment_id']);
            if ((int) $data['heavy_equipment_id'] !== $oldEquipmentId) {
                $data['daily_rate'] = $targetEquipment->daily_rate;
            }
            $data = $this->normalizeRentalData($data, $targetEquipment);

            $datesChanged = $data['start_date'] !== CarbonImmutable::parse($rental->start_date)->toDateString()
                || $data['end_date'] !== CarbonImmutable::parse($rental->end_date)->toDateString();

            if ($targetEquipment->machine_status === 'Maintenance'
                && ((int) $data['heavy_equipment_id'] !== $oldEquipmentId || $datesChanged)) {
                throw ValidationException::withMessages([
                    'heavy_equipment_id' => 'Equipment under maintenance cannot be assigned to a rental schedule.',
                ]);
            }

            $this->assertNoDateConflict($data, $rental->id);
            $rental->update($data);

            foreach ($equipment as $machine) {
                $this->refreshMachineStatus($machine);
            }

            return $rental->refresh()->load(['heavyEquipment', 'contractor', 'projectSite']);
        });
    }

    public function deleteRental(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $rental = EquipmentRental::query()->lockForUpdate()->findOrFail($id);
            $equipment = HeavyEquipment::query()->lockForUpdate()->findOrFail($rental->heavy_equipment_id);

            $rental->delete();
            $this->refreshMachineStatus($equipment);
        });
    }

    private function normalizeRentalData(array $data, HeavyEquipment $equipment): array
    {
        $data['daily_rate'] ??= $equipment->daily_rate;
        $data['fuel_liters_used'] ??= 0;
        $data['start_date'] = CarbonImmutable::parse($data['start_date'])->toDateString();
        $data['end_date'] = CarbonImmutable::parse($data['end_date'])->toDateString();

        if ($data['end_date'] < $data['start_date']) {
            throw ValidationException::withMessages([
                'end_date' => 'The rental end date must be on or after the start date.',
            ]);
        }

        return $data;
    }

    private function assertNoDateConflict(array $data, ?int $exceptId = null): void
    {
        $query = EquipmentRental::query()
            ->where('heavy_equipment_id', $data['heavy_equipment_id'])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date']);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'heavy_equipment_id' => 'This equipment already has a rental scheduled during those dates.',
            ]);
        }
    }

    private function refreshMachineStatus(HeavyEquipment $equipment): void
    {
        $hasActiveRental = $equipment->rentals()
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        if ($hasActiveRental) {
            $equipment->update(['machine_status' => 'Rented']);
        } elseif ($equipment->machine_status === 'Rented') {
            $equipment->update(['machine_status' => 'Available']);
        }
    }

}
