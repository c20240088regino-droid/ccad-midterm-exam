<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectSiteRequest;
use App\Services\ProjectSiteService;
use Illuminate\Http\JsonResponse;

class ProjectSiteController extends Controller
{
    public function __construct(private readonly ProjectSiteService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listSites());
    }

    public function create(): JsonResponse
    {
        return response()->json(['project_sites' => $this->service->siteOptions()]);
    }

    public function store(ProjectSiteRequest $request): JsonResponse
    {
        return response()->json($this->service->createSite($request->validated()), 201);
    }

    public function show(int $project_site): JsonResponse
    {
        return response()->json($this->service->findSite($project_site));
    }

    public function update(ProjectSiteRequest $request, int $project_site): JsonResponse
    {
        return response()->json($this->service->updateSite($project_site, $request->validated()));
    }

    public function destroy(int $project_site): JsonResponse
    {
        $this->service->deleteSite($project_site);

        return response()->json(['message' => 'Project site deleted.']);
    }
}
