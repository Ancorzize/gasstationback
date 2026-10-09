<?php

namespace App\Modules\Nomina\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollDeductionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_settlement_id' => $this->payroll_settlement_id,
            'type_code' => $this->type_code,
            'name' => $this->name,
            'percentage' => $this->percentage !== null ? (float) $this->percentage : null,
            'base_amount' => $this->base_amount !== null ? (float) $this->base_amount : null,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
