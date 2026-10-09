<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Modules\Nomina\Application\DTOs\CreatePayrollDeductionDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollDeductionDTO;

class PayrollDeductionMapper
{
    public static function fromArrayToCreateDTO(array $data, int $settlementId): CreatePayrollDeductionDTO
    {
        return new CreatePayrollDeductionDTO(
            payroll_settlement_id: $settlementId,
            type_code: $data['type_code'],
            concept_name: $data['concept_name'],
            base_amount: (float) $data['base_amount'],
            amount: (float) $data['amount'],
            percentage: isset($data['percentage']) ? (float) $data['percentage'] : null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdatePayrollDeductionDTO
    {
        return new UpdatePayrollDeductionDTO(
            concept_name: $data['concept_name'] ?? null,
            percentage: isset($data['percentage']) ? (float) $data['percentage'] : null,
            base_amount: isset($data['base_amount']) ? (float) $data['base_amount'] : null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
        );
    }
}
