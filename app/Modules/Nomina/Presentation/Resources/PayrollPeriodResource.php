<?php

namespace App\Modules\Nomina\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Nomina\Presentation\Resources\PayrollCycleResource;

class PayrollPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_cycle_id' => $this->payroll_cycle_id,
            'payroll_cycle' => new PayrollCycleResource($this->whenLoaded('payrollCycle')),
            'name' => $this->name,
            'start_date' => $this->start_date?->format('Y-m-d') ?? $this->start_date,
            'end_date' => $this->end_date?->format('Y-m-d') ?? $this->end_date,
            'payment_date' => $this->payment_date?->format('Y-m-d') ?? $this->payment_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
