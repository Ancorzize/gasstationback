<?php

namespace App\Modules\Nomina\Application\DTOs;

class CreatePayrollPeriodDTO
{
    public function __construct(
        public int $payroll_cycle_id,
        public string $name,
        public string $start_date,
        public string $end_date,
        public string $payment_date,
        public string $status = 'open',
    ) {}

    public function toArray(): array
    {
        return [
            'payroll_cycle_id' => $this->payroll_cycle_id,
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'payment_date' => $this->payment_date,
            'status' => $this->status,
        ];
    }
}
