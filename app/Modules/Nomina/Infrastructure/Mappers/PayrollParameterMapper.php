<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Modules\Nomina\Application\DTOs\CreatePayrollParameterDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollParameterDTO;

class PayrollParameterMapper
{
    public static function fromArrayToCreateDTO(array $data): CreatePayrollParameterDTO
    {
        return new CreatePayrollParameterDTO(
            year: (int) $data['year'],
            smlmv: (float) $data['smlmv'],
            transport_assistance: (float) $data['transport_assistance'],
            max_smlmv_for_transport: isset($data['max_smlmv_for_transport']) ? (float) $data['max_smlmv_for_transport'] : 2.00,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdatePayrollParameterDTO
    {
        return new UpdatePayrollParameterDTO(
            smlmv: isset($data['smlmv']) ? (float) $data['smlmv'] : null,
            transport_assistance: isset($data['transport_assistance']) ? (float) $data['transport_assistance'] : null,
            max_smlmv_for_transport: isset($data['max_smlmv_for_transport']) ? (float) $data['max_smlmv_for_transport'] : null,
        );
    }
}
