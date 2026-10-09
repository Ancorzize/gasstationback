<?php

namespace App\Modules\Empleados\Application\DTOs;

class CreateEmployeeDTO
{
    public function __construct(
        public ?int $user_id,
        public string $worker_code,
        public string $identity_document_id,
        public string $document_number,
        public string $first_name,
        public ?string $other_names,
        public string $first_surname,
        public ?string $second_surname,
        public string $working_country_id,
        public ?string $work_city_id,
        public string $work_address,
        public string $date_entry,
        public ?string $departure_date,
        public string $worker_type_id,
        public string $worker_subtype_id,
        public string $contract_type_id,
        public float $salary,
        public bool $integral_salary,
        public bool $high_risk_pension,
        public bool $is_active = true
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->user_id,
            'worker_code' => $this->worker_code,
            'identity_document_id' => $this->identity_document_id,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'other_names' => $this->other_names,
            'first_surname' => $this->first_surname,
            'second_surname' => $this->second_surname,
            'working_country_id' => $this->working_country_id,
            'work_city_id' => $this->work_city_id,
            'work_address' => $this->work_address,
            'date_entry' => $this->date_entry,
            'departure_date' => $this->departure_date,
            'worker_type_id' => $this->worker_type_id,
            'worker_subtype_id' => $this->worker_subtype_id,
            'contract_type_id' => $this->contract_type_id,
            'salary' => $this->salary,
            'integral_salary' => $this->integral_salary,
            'high_risk_pension' => $this->high_risk_pension,
            'is_active' => $this->is_active,
        ], fn($val) => $val !== null);
    }
}
