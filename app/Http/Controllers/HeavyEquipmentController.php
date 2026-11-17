<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHeavyEquipmentRequest;
use App\Http\Requests\UpdateHeavyEquipmentRequest;
use App\Services\HeavyEquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HeavyEquipmentController extends Controller
{
    public function __construct(private readonly HeavyEquipmentService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listEquipment());
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'statuses' => $this->service->statuses(),
        ]);
    }

    public function registrationPage(): Response
    {
        return Inertia::render('register-equipment');
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->findEquipment($id));
    }

    public function store(StoreHeavyEquipmentRequest $request): JsonResponse|RedirectResponse
    {
        $equipment = $this->service->createEquipment($request->validated());

        if ($request->header('X-Inertia')) {
            return redirect()->route('dashboard')->with('success', 'Equipment registered successfully.');
        }

        return response()->json($equipment, 201);
    }

    public function update(UpdateHeavyEquipmentRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->updateEquipment($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteEquipment($id);

        return response()->json(['message' => 'Equipment deleted.']);
    }
}
