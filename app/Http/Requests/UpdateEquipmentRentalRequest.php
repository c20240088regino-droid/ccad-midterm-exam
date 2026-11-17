<?php

namespace App\Http\Requests;

class UpdateEquipmentRentalRequest extends EquipmentRentalRequest
{
    public function rules(): array
    {
        return $this->rentalRules(true);
    }
}
