<?php

namespace App\Modules\Nomina\Application\DTOs;

class CreatePayrollCycleDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public string $period_type,
        public bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'period_type' => strtolower($this->period_type),
            'is_active' => $this->is_active,
        ];
    }
}
