<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    protected $fillable = ['contractor_name'];

    public function rentals(): HasMany
    {
        return $this->hasMany(equipmentRental::class);
    }
}