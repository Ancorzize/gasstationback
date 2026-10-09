<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Modules\Nomina\Application\DTOs\CreatePayrollSettlementDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollSettlementDTO;

class PayrollSettlementMapper
{
    public static function fromArrayToCreateDTO(array $data, int $periodId): CreatePayrollSettlementDTO
    {
        return new CreatePayrollSettlementDTO(
            payroll_period_id: $periodId,
            employee_id: (int) $data['employee_id'],
            days_worked: (int) $data['days_worked'],
            status: $data['status'] ?? 'draft',
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdatePayrollSettlementDTO
    {
        return new UpdatePayrollSettlementDTO(
            days_worked: isset($data['days_worked']) ? (int) $data['days_worked'] : null,
            status: $data['status'] ?? null,
        );
    }
}
