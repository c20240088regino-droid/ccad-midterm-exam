<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHeavyEquipmentRequest;
use App\Http\Requests\UpdateHeavyEquipmentRequest;
use App\Models\HeavyEquipment;
use App\Services\EquipmentRentalService;
use Illuminate\Http\JsonResponse;

class HeavyEquipmentController extends Controller
{
    public function __construct(private readonly EquipmentRentalService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(
            HeavyEquipment::query()->with('rentals')->latest()->paginate(15)
        );
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'statuses' => ['Available', 'Maintenance'],
        ]);
    }

    public function store(StoreHeavyEquipmentRequest $request): JsonResponse
    {
        $equipment = $this->service->createEquipment($request->validated());

        return response()->json($equipment, 201);
    }

    public function update(UpdateHeavyEquipmentRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->updateEquipment($id, $request->validated()));
    }
}
