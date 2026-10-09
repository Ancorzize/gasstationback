<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Modules\Nomina\Application\DTOs\CreateEmployeePayrollCycleDTO;
use App\Modules\Nomina\Application\DTOs\UpdateEmployeePayrollCycleDTO;

class EmployeePayrollCycleMapper
{
    public static function fromArrayToCreateDTO(array $data, int $employeeId): CreateEmployeePayrollCycleDTO
    {
        return new CreateEmployeePayrollCycleDTO(
            employee_id: $employeeId,
            payroll_cycle_id: (int) $data['payroll_cycle_id'],
            start_date: $data['start_date'],
            end_date: $data['end_date'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateEmployeePayrollCycleDTO
    {
        return new UpdateEmployeePayrollCycleDTO(
            payroll_cycle_id: isset($data['payroll_cycle_id']) ? (int) $data['payroll_cycle_id'] : null,
            start_date: $data['start_date'] ?? null,
            end_date: $data['end_date'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }
}
