<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentRental extends Model
{
    protected $appends = ['total_days', 'total_rental_cost'];

    protected $fillable = [
        'heavy_equipment_id',
        'contractor_id',
        'project_site_id',
        'start_date',
        'end_date',
        'daily_rate',
        'fuel_liters_used',
        'fuel_rate',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'daily_rate' => 'decimal:2',
            'fuel_liters_used' => 'decimal:2',
            'fuel_rate' => 'decimal:2',
        ];
    }

    public function heavyEquipment(): BelongsTo
    {
        return $this->belongsTo(HeavyEquipment::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function getTotalDaysAttribute(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getTotalRentalCostAttribute(): float
    {
        return round(
            ((float) $this->daily_rate * $this->total_days)
                + ((float) $this->fuel_liters_used * (float) $this->fuel_rate),
            2
        );
    }

}