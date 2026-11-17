<?php

namespace App\Services;

use App\Models\HeavyEquipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HeavyEquipmentService
{
    public function listEquipment(int $perPage = 15): LengthAwarePaginator
    {
        return HeavyEquipment::query()
            ->with('rentals')
            ->latest()
            ->paginate($perPage);
    }

    public function availableEquipment(): Collection
    {
        return HeavyEquipment::query()
            ->where('machine_status', 'Available')
            ->orderBy('equipment_tag')
            ->get(['id', 'equipment_tag', 'machine_model', 'daily_rate', 'machine_status']);
    }

    public function equipmentOptions(): Collection
    {
        return HeavyEquipment::query()
            ->where('machine_status', '!=', 'Maintenance')
            ->orderBy('equipment_tag')
            ->get();
    }

    public function findEquipment(int $id): HeavyEquipment
    {
        return HeavyEquipment::query()->with('rentals')->findOrFail($id);
    }

    public function createEquipment(array $data): HeavyEquipment
    {
        $data = $this->normalize($data);
        $data['machine_status'] ??= 'Available';

        return DB::transaction(fn (): HeavyEquipment => HeavyEquipment::query()->create($data));
    }

    public function updateEquipment(int $id, array $data): HeavyEquipment
    {
        return DB::transaction(function () use ($id, $data): HeavyEquipment {
            $equipment = HeavyEquipment::query()->lockForUpdate()->findOrFail($id);
            $data = $this->normalize($data);

            if (isset($data['machine_status']) && $equipment->rentals()
                ->whereDate('start_date', '<=', now()->toDateString())
                ->whereDate('end_date', '>=', now()->toDateString())
                ->exists()) {
                throw ValidationException::withMessages([
                    'machine_status' => 'Equipment with an active rental cannot be manually marked available or under maintenance.',
                ]);
            }

            $equipment->update($data);

            return $equipment->refresh();
        });
    }

    public function deleteEquipment(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $equipment = HeavyEquipment::query()->lockForUpdate()->findOrFail($id);

            if ($equipment->rentals()->exists()) {
                throw ValidationException::withMessages([
                    'equipment' => 'Equipment with rental records cannot be deleted.',
                ]);
            }

            $equipment->delete();
        });
    }

    public function statuses(): array
    {
        return ['Available', 'Maintenance'];
    }

    private function normalize(array $data): array
    {
        foreach (['equipment_tag', 'machine_model'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim(preg_replace('/\\s+/u', ' ', strip_tags($data[$field])) ?? '');
            }
        }

        if (isset($data['equipment_tag'])) {
            $data['equipment_tag'] = mb_strtoupper($data['equipment_tag']);
        }

        return $data;
    }
}
