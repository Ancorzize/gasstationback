<?php

namespace App\Modules\Empleados\Application\DTOs;

class CreateEmployeePaymentAccountDTO
{
    public function __construct(
        public int $employee_id,
        public ?string $payment_method_id,
        public ?string $means_payment_id,
        public ?string $bank,
        public ?string $account_type,
        public ?string $account_number,
        public bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'employee_id' => $this->employee_id,
            'payment_method_id' => $this->payment_method_id,
            'means_payment_id' => $this->means_payment_id,
            'bank' => $this->bank,
            'account_type' => $this->account_type,
            'account_number' => $this->account_number,
            'is_active' => $this->is_active,
        ], fn ($val) => $val !== null);
    }
}
