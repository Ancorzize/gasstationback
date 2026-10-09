<?php

namespace App\Modules\Empleados\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'worker_code' => $this->worker_code,
            'identity_document_id' => $this->identity_document_id,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'other_names' => $this->other_names,
            'first_surname' => $this->first_surname,
            'second_surname' => $this->second_surname,
            'full_name' => trim(implode(' ', array_filter([
                $this->first_name,
                $this->other_names,
                $this->first_surname,
                $this->second_surname
            ]))),
            'working_country_id' => $this->working_country_id,
            'work_city_id' => $this->work_city_id,
            'work_address' => $this->work_address,
            'date_entry' => $this->date_entry?->format('Y-m-d') ?? $this->date_entry,
            'departure_date' => $this->departure_date?->format('Y-m-d') ?? $this->departure_date,
            'worker_type_id' => $this->worker_type_id,
            'worker_subtype_id' => $this->worker_subtype_id,
            'contract_type_id' => $this->contract_type_id,
            'salary' => (float) $this->salary,
            'integral_salary' => (bool) $this->integral_salary,
            'high_risk_pension' => (bool) $this->high_risk_pension,
            'is_active' => (bool) $this->is_active,
            'payment_accounts' => EmployeePaymentAccountResource::collection($this->whenLoaded('paymentAccounts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
