<?php

namespace App\Modules\Nomina\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Nomina\Presentation\Resources\PayrollCycleResource;

class EmployeePayrollCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'payroll_cycle_id' => $this->payroll_cycle_id,
            'payroll_cycle' => new PayrollCycleResource($this->whenLoaded('payrollCycle')),
            'start_date' => $this->start_date?->format('Y-m-d') ?? $this->start_date,
            'end_date' => $this->end_date?->format('Y-m-d') ?? $this->end_date,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
