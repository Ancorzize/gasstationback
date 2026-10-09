<?php

namespace App\Modules\Nomina\Application\DTOs;

class UpdateEmployeePayrollCycleDTO
{
    public function __construct(
        public ?int $payroll_cycle_id = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?bool $is_active = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        if ($this->payroll_cycle_id !== null) {
            $data['payroll_cycle_id'] = $this->payroll_cycle_id;
        }
        if ($this->start_date !== null) {
            $data['start_date'] = $this->start_date;
        }
        if ($this->end_date !== null) {
            $data['end_date'] = $this->end_date;
        }
        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }
        return $data;
    }
}
