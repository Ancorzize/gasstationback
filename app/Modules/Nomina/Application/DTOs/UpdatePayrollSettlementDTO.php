<?php

namespace App\Modules\Nomina\Application\DTOs;

class UpdatePayrollSettlementDTO
{
    public function __construct(
        public ?int $days_worked = null,
        public ?string $status = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        if ($this->days_worked !== null) {
            $data['days_worked'] = $this->days_worked;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        return $data;
    }
}
