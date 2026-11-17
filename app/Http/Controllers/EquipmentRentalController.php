<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRentalRequest;
use App\Http\Requests\UpdateEquipmentRentalRequest;
use App\Services\EquipmentRentalService;
use Illuminate\Http\JsonResponse;

class EquipmentRentalController extends Controller
{
    public function __construct(private readonly EquipmentRentalService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listRentals());
    }

    public function create(): JsonResponse
    {
        return response()->json($this->service->rentalFormOptions());
    }

    public function store(StoreEquipmentRentalRequest $request): JsonResponse
    {
        $rental = $this->service->createRental($request->validated());

        return response()->json($rental, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->findRental($id));
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
