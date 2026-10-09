<?php

namespace App\Modules\Nomina\Application\DTOs;

class UpdatePayrollDeductionDTO
{
    public function __construct(
        public ?string $concept_name = null,
        public ?float $percentage = null,
        public ?float $base_amount = null,
        public ?float $amount = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        if ($this->concept_name !== null) {
            $data['concept_name'] = $this->concept_name;
        }
        if ($this->percentage !== null) {
            $data['percentage'] = $this->percentage;
        }
        if ($this->base_amount !== null) {
            $data['base_amount'] = $this->base_amount;
        }
        if ($this->amount !== null) {
            $data['amount'] = $this->amount;
        }
        return $data;
    }
}
