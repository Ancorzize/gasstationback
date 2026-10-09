<?php

namespace App\Modules\Nomina\Application\DTOs;

class CreatePayrollParameterDTO
{
    public function __construct(
        public int $year,
        public float $smlmv,
        public float $transport_assistance,
        public float $max_smlmv_for_transport = 2.00,
    ) {}

    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'smlmv' => $this->smlmv,
            'transport_assistance' => $this->transport_assistance,
            'max_smlmv_for_transport' => $this->max_smlmv_for_transport,
        ];
    }
}
