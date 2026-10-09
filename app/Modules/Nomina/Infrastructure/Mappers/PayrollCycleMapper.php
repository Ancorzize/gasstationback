<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Modules\Nomina\Application\DTOs\CreatePayrollCycleDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollCycleDTO;

class PayrollCycleMapper
{
    public static function fromArrayToCreateDTO(array $data): CreatePayrollCycleDTO
    {
        return new CreatePayrollCycleDTO(
            name: $data['name'],
            code: $data['code'],
            period_type: $data['period_type'],
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdatePayrollCycleDTO
    {
        return new UpdatePayrollCycleDTO(
            name: $data['name'] ?? null,
            code: $data['code'] ?? null,
            period_type: $data['period_type'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }
}
