<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Modules\Nomina\Application\DTOs\CreatePayrollPeriodDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollPeriodDTO;

class PayrollPeriodMapper
{
    public static function fromArrayToCreateDTO(array $data, int $cycleId): CreatePayrollPeriodDTO
    {
        return new CreatePayrollPeriodDTO(
            payroll_cycle_id: $cycleId,
            name: $data['name'],
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            payment_date: $data['payment_date'],
            status: $data['status'] ?? 'open',
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdatePayrollPeriodDTO
    {
        return new UpdatePayrollPeriodDTO(
            name: $data['name'] ?? null,
            start_date: $data['start_date'] ?? null,
            end_date: $data['end_date'] ?? null,
            payment_date: $data['payment_date'] ?? null,
            status: $data['status'] ?? null,
        );
    }
}
