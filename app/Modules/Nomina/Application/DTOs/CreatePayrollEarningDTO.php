<?php

namespace App\Modules\Nomina\Application\DTOs;

class CreatePayrollEarningDTO
{
    public function __construct(
        public int $payroll_settlement_id,
        public string $type_code,
        public string $concept_name,
        public float $amount,
        public ?float $quantity = null,
        public ?float $percentage = null,
        public ?float $unit_value = null,
        public bool $is_taxable = true,
    ) {}

    public function toArray(): array
    {
        return [
            'payroll_settlement_id' => $this->payroll_settlement_id,
            'type_code' => $this->type_code,
            'concept_name' => $this->concept_name,
            'quantity' => $this->quantity,
            'percentage' => $this->percentage,
            'unit_value' => $this->unit_value,
            'amount' => $this->amount,
            'is_taxable' => $this->is_taxable,
        ];
    }
}
