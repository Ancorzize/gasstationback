<?php

namespace App\Modules\Nomina\Application\DTOs;

class CreatePayrollDeductionDTO
{
    public function __construct(
        public int $payroll_settlement_id,
        public string $type_code,
        public string $concept_name,
        public float $base_amount,
        public float $amount,
        public ?float $percentage = null,
    ) {}

    public function toArray(): array
    {
        return [
            'payroll_settlement_id' => $this->payroll_settlement_id,
            'type_code' => $this->type_code,
            'concept_name' => $this->concept_name,
            'percentage' => $this->percentage,
            'base_amount' => $this->base_amount,
            'amount' => $this->amount,
        ];
    }
}
