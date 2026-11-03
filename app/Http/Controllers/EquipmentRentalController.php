<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRentalRequest;
use App\Http\Requests\UpdateEquipmentRentalRequest;
use App\Models\Contractor;
use App\Models\EquipmentRental;
use App\Models\HeavyEquipment;
use App\Models\ProjectSite;
use App\Services\EquipmentRentalService;
use Illuminate\Http\JsonResponse;

class EquipmentRentalController extends Controller
{
    public function __construct(private readonly EquipmentRentalService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(
            EquipmentRental::query()
                ->with(['heavyEquipment', 'contractor', 'projectSite'])
                ->latest()
                ->paginate(15)
        );
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'equipment' => HeavyEquipment::query()->where('machine_status', '!=', 'Maintenance')->orderBy('equipment_tag')->get(),
            'contractors' => Contractor::query()->orderBy('contractor_name')->get(),
            'project_sites' => ProjectSite::query()->orderBy('project_site')->get(),
        ]);
    }

    public function store(StoreEquipmentRentalRequest $request): JsonResponse
    {
        $rental = $this->service->createRental($request->validated());

        return response()->json($rental, 201);
    }

    public function update(UpdateEquipmentRentalRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->updateRental($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteRental($id);

        return response()->json(['message' => 'Rental deleted.']);
    }
}
