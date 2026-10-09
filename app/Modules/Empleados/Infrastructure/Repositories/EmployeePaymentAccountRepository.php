<?php

namespace App\Modules\Empleados\Infrastructure\Repositories;

use App\Models\EmployeePaymentAccount;
use App\Modules\Empleados\Application\Interfaces\EmployeePaymentAccountRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EmployeePaymentAccountRepository implements EmployeePaymentAccountRepositoryInterface
{
    public function getByEmployeeId(int $employeeId): Collection
    {
        return EmployeePaymentAccount::where('employee_id', $employeeId)
            ->orderBy('is_active', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function findById(int $id): ?EmployeePaymentAccount
    {
        return EmployeePaymentAccount::find($id);
    }

    public function create(array $data): EmployeePaymentAccount
    {
        return EmployeePaymentAccount::create($data);
    }

    public function update(int $id, array $data): EmployeePaymentAccount
    {
        $account = $this->findById($id);
        $account->update($data);
        return $account->fresh();
    }

    public function changeStatus(int $id, bool $isActive): EmployeePaymentAccount
    {
        $account = $this->findById($id);
        $account->update(['is_active' => $isActive]);
        return $account->fresh();
    }

    public function deactivateOthersForEmployee(int $employeeId, int $currentAccountId): void
    {
        EmployeePaymentAccount::where('employee_id', $employeeId)
            ->where('id', '!=', $currentAccountId)
            ->update(['is_active' => false]);
    }
}
