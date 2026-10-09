<?php

namespace App\Modules\Nomina\Application\DTOs;

class UpdatePayrollParameterDTO
{
    public function __construct(
        public ?float $smlmv = null,
        public ?float $transport_assistance = null,
        public ?float $max_smlmv_for_transport = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        if ($this->smlmv !== null) {
            $data['smlmv'] = $this->smlmv;
        }
        if ($this->transport_assistance !== null) {
            $data['transport_assistance'] = $this->transport_assistance;
        }
        if ($this->max_smlmv_for_transport !== null) {
            $data['max_smlmv_for_transport'] = $this->max_smlmv_for_transport;
        }
        return $data;
    }
}
