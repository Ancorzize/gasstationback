<?php

namespace App\Modules\Empleados\Application\Services;

use App\Models\EmployeePaymentAccount;
use App\Modules\Empleados\Application\DTOs\CreateEmployeePaymentAccountDTO;
use App\Modules\Empleados\Application\DTOs\UpdateEmployeePaymentAccountDTO;
use App\Modules\Empleados\Application\Interfaces\EmployeePaymentAccountRepositoryInterface;
use App\Modules\Empleados\Application\Services\EmployeeService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class EmployeePaymentAccountService
{
    public function __construct(
        protected EmployeePaymentAccountRepositoryInterface $repository,
        protected EmployeeService $employeeService
    ) {}

    public function getByEmployeeId(int $employeeId)
    {
        $this->employeeService->findById($employeeId);
        return $this->repository->getByEmployeeId($employeeId);
    }

    public function findById(int $id): EmployeePaymentAccount
    {
        $account = $this->repository->findById($id);
        if (!$account) {
            throw new HttpException(404, "Cuenta de pago #{$id} no encontrada.");
        }
        return $account;
    }

    public function create(CreateEmployeePaymentAccountDTO $dto): EmployeePaymentAccount
    {
        $this->employeeService->findById($dto->employee_id);

        return DB::transaction(function () use ($dto) {
            $account = $this->repository->create($dto->toArray());

            if ($account->is_active) {
                $this->repository->deactivateOthersForEmployee($account->employee_id, $account->id);
            }

            return $account;
        });
    }

    public function update(int $id, UpdateEmployeePaymentAccountDTO $dto): EmployeePaymentAccount
    {
        $account = $this->findById($id);

        return DB::transaction(function () use ($id, $account, $dto) {
            $updatedAccount = $this->repository->update($id, $dto->toArray());

            if ($updatedAccount->is_active) {
                $this->repository->deactivateOthersForEmployee($updatedAccount->employee_id, $updatedAccount->id);
            }

            return $updatedAccount;
        });
    }

    public function changeStatus(int $id, bool $isActive): EmployeePaymentAccount
    {
        $account = $this->findById($id);

        return DB::transaction(function () use ($id, $account, $isActive) {
            $updatedAccount = $this->repository->changeStatus($id, $isActive);

            if ($isActive) {
                $this->repository->deactivateOthersForEmployee($updatedAccount->employee_id, $updatedAccount->id);
            }

            return $updatedAccount;
        });
    }
}
