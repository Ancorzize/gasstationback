<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Modules\Nomina\Application\DTOs\CreatePayrollEarningDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollEarningDTO;

class PayrollEarningMapper
{
    public static function fromArrayToCreateDTO(array $data, int $settlementId): CreatePayrollEarningDTO
    {
        return new CreatePayrollEarningDTO(
            payroll_settlement_id: $settlementId,
            type_code: $data['type_code'],
            concept_name: $data['concept_name'],
            amount: (float) $data['amount'],
            quantity: isset($data['quantity']) ? (float) $data['quantity'] : null,
            percentage: isset($data['percentage']) ? (float) $data['percentage'] : null,
            unit_value: isset($data['unit_value']) ? (float) $data['unit_value'] : null,
            is_taxable: isset($data['is_taxable']) ? (bool) $data['is_taxable'] : true,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdatePayrollEarningDTO
    {
        return new UpdatePayrollEarningDTO(
            concept_name: $data['concept_name'] ?? null,
            quantity: isset($data['quantity']) ? (float) $data['quantity'] : null,
            percentage: isset($data['percentage']) ? (float) $data['percentage'] : null,
            unit_value: isset($data['unit_value']) ? (float) $data['unit_value'] : null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            is_taxable: isset($data['is_taxable']) ? (bool) $data['is_taxable'] : null,
        );
    }
}
