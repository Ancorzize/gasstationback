<?php

namespace App\Modules\Nomina\Application\DTOs;

class CreatePayrollSettlementDTO
{
    public function __construct(
        public int $payroll_period_id,
        public int $employee_id,
        public int $days_worked,
        public string $status = 'draft',
    ) {}

    public function toArray(): array
    {
        return [
            'payroll_period_id' => $this->payroll_period_id,
            'employee_id' => $this->employee_id,
            'days_worked' => $this->days_worked,
            'status' => $this->status,
        ];
    }
}
