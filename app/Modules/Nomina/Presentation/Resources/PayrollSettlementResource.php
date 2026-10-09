<?php

namespace App\Modules\Nomina\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Nomina\Presentation\Resources\PayrollPeriodResource;
use App\Modules\Empleados\Presentation\Resources\EmployeeResource;
use App\Modules\Nomina\Presentation\Resources\PayrollEarningResource;
use App\Modules\Nomina\Presentation\Resources\PayrollDeductionResource;

class PayrollSettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_period_id' => $this->payroll_period_id,
            'payroll_period' => new PayrollPeriodResource($this->whenLoaded('payrollPeriod')),
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),

            // Snapshot histórico del empleado
            'snapshot' => [
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
                'worker_type_id' => $this->worker_type_id,
                'worker_subtype_id' => $this->worker_subtype_id,
                'contract_type_id' => $this->contract_type_id,
                'date_entry' => $this->date_entry?->format('Y-m-d') ?? $this->date_entry,
                'departure_date' => $this->departure_date?->format('Y-m-d') ?? $this->departure_date,
                'salary' => (float) $this->salary,
                'integral_salary' => (bool) $this->integral_salary,
            ],

            // Datos de liquidación
            'days_worked' => (int) $this->days_worked,
            'basic_salary' => (float) $this->basic_salary,
            'total_earned' => (float) $this->total_earned,
            'deductions_total' => (float) $this->deductions_total,
            'total_voucher' => (float) $this->total_voucher,
            'status' => $this->status,

            'earnings' => PayrollEarningResource::collection($this->whenLoaded('earnings')),
            'deductions' => PayrollDeductionResource::collection($this->whenLoaded('deductions')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
