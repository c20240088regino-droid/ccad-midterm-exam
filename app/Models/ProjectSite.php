<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSite extends Model
{
    protected $fillable = ['project_site'];

    public function rentals(): HasMany
    {
        return $this->hasMany(EquipmentRental::class);
    }
}