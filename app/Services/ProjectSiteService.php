<?php

namespace App\Services;

use App\Models\ProjectSite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectSiteService
{
    public function listSites(int $perPage = 15): LengthAwarePaginator
    {
        return ProjectSite::query()->orderBy('project_site')->paginate($perPage);
    }

    public function siteOptions(): Collection
    {
        return ProjectSite::query()->orderBy('project_site')->get();
    }

    public function findSite(int $id): ProjectSite
    {
        return ProjectSite::query()->with('rentals')->findOrFail($id);
    }

    public function createSite(array $data): ProjectSite
    {
        return DB::transaction(function () use ($data): ProjectSite {
            $data = $this->sanitize($data);

            return ProjectSite::query()->firstOrCreate($data);
        });
    }

    public function updateSite(int $id, array $data): ProjectSite
    {
        return DB::transaction(function () use ($id, $data): ProjectSite {
            $site = ProjectSite::query()->lockForUpdate()->findOrFail($id);
            $site->update($this->sanitize($data));

            return $site->refresh();
        });
    }

    public function deleteSite(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $site = ProjectSite::query()->lockForUpdate()->findOrFail($id);

            if ($site->rentals()->exists()) {
                throw ValidationException::withMessages([
                    'project_site' => 'A project site with rental records cannot be deleted.',
                ]);
            }

            $site->delete();
        });
    }

    private function sanitize(array $data): array
    {
        if (isset($data['project_site'])) {
            $data['project_site'] = trim(
                preg_replace('/\s+/u', ' ', strip_tags($data['project_site'])) ?? ''
            );
        }

        return $data;
    }
}
