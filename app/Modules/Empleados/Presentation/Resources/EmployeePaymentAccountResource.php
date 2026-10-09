<?php

namespace App\Modules\Empleados\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeePaymentAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'payment_method_id' => $this->payment_method_id,
            'means_payment_id' => $this->means_payment_id,
            'bank' => $this->bank,
            'account_type' => $this->account_type,
            'account_number' => $this->account_number,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
