<?php

namespace App\Modules\Nomina\Application\DTOs;

class UpdatePayrollCycleDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $code = null,
        public ?string $period_type = null,
        public ?bool $is_active = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->code !== null) {
            $data['code'] = strtoupper($this->code);
        }
        if ($this->period_type !== null) {
            $data['period_type'] = strtolower($this->period_type);
        }
        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }
        return $data;
    }
}
