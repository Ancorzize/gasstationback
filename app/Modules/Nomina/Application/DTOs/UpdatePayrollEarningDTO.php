<?php

namespace App\Modules\Nomina\Application\DTOs;

class UpdatePayrollEarningDTO
{
    public function __construct(
        public ?string $concept_name = null,
        public ?float $quantity = null,
        public ?float $percentage = null,
        public ?float $unit_value = null,
        public ?float $amount = null,
        public ?bool $is_taxable = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        if ($this->concept_name !== null) {
            $data['concept_name'] = $this->concept_name;
        }
        if ($this->quantity !== null) {
            $data['quantity'] = $this->quantity;
        }
        if ($this->percentage !== null) {
            $data['percentage'] = $this->percentage;
        }
        if ($this->unit_value !== null) {
            $data['unit_value'] = $this->unit_value;
        }
        if ($this->amount !== null) {
            $data['amount'] = $this->amount;
        }
        if ($this->is_taxable !== null) {
            $data['is_taxable'] = $this->is_taxable;
        }
        return $data;
    }
}
