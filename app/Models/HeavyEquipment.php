<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeavyEquipment extends Model
{
    protected $table = 'heavy_equipment';

    protected $fillable = [
        'equipment_tag',
        'machine_model',
        'daily_rate',
        'machine_status',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
        ];
    }
    
    public function rentals(): HasMany
    {
        return $this->hasMany(EquipmentRental::class);
    }
}