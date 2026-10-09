<?php

namespace App\Modules\Empleados\Application\DTOs;

class UpdateEmployeePaymentAccountDTO
{
    public function __construct(
        public ?string $payment_method_id = null,
        public ?string $means_payment_id = null,
        public ?string $bank = null,
        public ?string $account_type = null,
        public ?string $account_number = null,
        public ?bool $is_active = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'payment_method_id' => $this->payment_method_id,
            'means_payment_id' => $this->means_payment_id,
            'bank' => $this->bank,
            'account_type' => $this->account_type,
            'account_number' => $this->account_number,
            'is_active' => $this->is_active,
        ], fn ($val) => $val !== null);
    }
}
