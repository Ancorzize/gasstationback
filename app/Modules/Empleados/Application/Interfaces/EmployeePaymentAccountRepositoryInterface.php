<?php

namespace App\Modules\Empleados\Application\Interfaces;

use App\Models\EmployeePaymentAccount;
use Illuminate\Database\Eloquent\Collection;

interface EmployeePaymentAccountRepositoryInterface
{
    public function getByEmployeeId(int $employeeId): Collection;
    public function findById(int $id): ?EmployeePaymentAccount;
    public function create(array $data): EmployeePaymentAccount;
    public function update(int $id, array $data): EmployeePaymentAccount;
    public function changeStatus(int $id, bool $isActive): EmployeePaymentAccount;
    public function deactivateOthersForEmployee(int $employeeId, int $currentAccountId): void;
}
