<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractorRequest;
use App\Services\ContractorService;
use Illuminate\Http\JsonResponse;

class ContractorController extends Controller
{
    public function __construct(private readonly ContractorService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listContractors());
    }

    public function create(): JsonResponse
    {
        return response()->json(['contractors' => $this->service->contractorOptions()]);
    }

    public function store(ContractorRequest $request): JsonResponse
    {
        return response()->json($this->service->createContractor($request->validated()), 201);
    }

    public function show(int $contractor): JsonResponse
    {
        return response()->json($this->service->findContractor($contractor));
    }

    public function update(ContractorRequest $request, int $contractor): JsonResponse
    {
        return response()->json($this->service->updateContractor($contractor, $request->validated()));
    }

    public function destroy(int $contractor): JsonResponse
    {
        $this->service->deleteContractor($contractor);

        return response()->json(['message' => 'Contractor deleted.']);
    }
}
