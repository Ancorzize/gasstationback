<?php

namespace App\Modules\Nomina\Application\DTOs;

class UpdatePayrollPeriodDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?string $payment_date = null,
        public ?string $status = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->start_date !== null) {
            $data['start_date'] = $this->start_date;
        }
        if ($this->end_date !== null) {
            $data['end_date'] = $this->end_date;
        }
        if ($this->payment_date !== null) {
            $data['payment_date'] = $this->payment_date;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        return $data;
    }
}
