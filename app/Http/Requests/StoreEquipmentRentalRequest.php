<?php

namespace App\Http\Requests;

class StoreEquipmentRentalRequest extends EquipmentRentalRequest
{
    public function rules(): array
    {
        return $this->rentalRules(false);
    }
}
