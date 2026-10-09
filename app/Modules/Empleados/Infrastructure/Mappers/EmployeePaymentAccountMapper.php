<?php

namespace App\Modules\Empleados\Infrastructure\Mappers;

use App\Modules\Empleados\Application\DTOs\CreateEmployeePaymentAccountDTO;
use App\Modules\Empleados\Application\DTOs\UpdateEmployeePaymentAccountDTO;

class EmployeePaymentAccountMapper
{
    public static function fromArrayToCreateDTO(array $data, int $employeeId): CreateEmployeePaymentAccountDTO
    {
        return new CreateEmployeePaymentAccountDTO(
            employee_id: $employeeId,
            payment_method_id: $data['payment_method_id'] ?? null,
            means_payment_id: $data['means_payment_id'] ?? null,
            bank: $data['bank'] ?? null,
            account_type: $data['account_type'] ?? null,
            account_number: $data['account_number'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateEmployeePaymentAccountDTO
    {
        return new UpdateEmployeePaymentAccountDTO(
            payment_method_id: $data['payment_method_id'] ?? null,
            means_payment_id: $data['means_payment_id'] ?? null,
            bank: $data['bank'] ?? null,
            account_type: $data['account_type'] ?? null,
            account_number: $data['account_number'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }
}
