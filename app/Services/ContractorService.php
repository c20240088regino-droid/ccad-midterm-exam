<?php

namespace App\Services;

use App\Models\Contractor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractorService
{
    public function listContractors(int $perPage = 15): LengthAwarePaginator
    {
        return Contractor::query()->orderBy('contractor_name')->paginate($perPage);
    }

    public function contractorOptions(): Collection
    {
        return Contractor::query()->orderBy('contractor_name')->get();
    }

    public function findContractor(int $id): Contractor
    {
        return Contractor::query()->with('rentals')->findOrFail($id);
    }

    public function createContractor(array $data): Contractor
    {
        return DB::transaction(function () use ($data): Contractor {
            $data = $this->normalize($data);

            return Contractor::query()->firstOrCreate($data);
        });
    }

    public function updateContractor(int $id, array $data): Contractor
    {
        return DB::transaction(function () use ($id, $data): Contractor {
            $contractor = Contractor::query()->lockForUpdate()->findOrFail($id);
            $contractor->update($this->normalize($data));

            return $contractor->refresh();
        });
    }

    public function deleteContractor(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $contractor = Contractor::query()->lockForUpdate()->findOrFail($id);

            if ($contractor->rentals()->exists()) {
                throw ValidationException::withMessages([
                    'contractor' => 'A contractor with rental records cannot be deleted.',
                ]);
            }

            $contractor->delete();
        });
    }

    private function normalize(array $data): array
    {
        if (isset($data['contractor_name'])) {
            $name = preg_replace('/\\s+/u', ' ', strip_tags($data['contractor_name'])) ?? '';
            $data['contractor_name'] = trim($name);
        }

        return $data;
    }
}