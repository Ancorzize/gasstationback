<?php

namespace App\Modules\Nomina\Application\DTOs;

class CreateEmployeePayrollCycleDTO
{
    public function __construct(
        public int $employee_id,
        public int $payroll_cycle_id,
        public string $start_date,
        public ?string $end_date = null,
        public bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'payroll_cycle_id' => $this->payroll_cycle_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
        ];
    }
}
