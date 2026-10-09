<?php

namespace App\Modules\Empleados\Infrastructure\Mappers;

use App\Modules\Empleados\Application\DTOs\CreateEmployeeDTO;
use App\Modules\Empleados\Application\DTOs\UpdateEmployeeDTO;

class EmployeeMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateEmployeeDTO
    {
        return new CreateEmployeeDTO(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            worker_code: (string) $data['worker_code'],
            identity_document_id: (string) ($data['identity_document_id'] ?? '13'),
            document_number: (string) $data['document_number'],
            first_name: (string) $data['first_name'],
            other_names: isset($data['other_names']) ? (string) $data['other_names'] : null,
            first_surname: (string) $data['first_surname'],
            second_surname: isset($data['second_surname']) ? (string) $data['second_surname'] : null,
            working_country_id: (string) ($data['working_country_id'] ?? '45'),
            work_city_id: isset($data['work_city_id']) ? (string) $data['work_city_id'] : null,
            work_address: (string) $data['work_address'],
            date_entry: (string) $data['date_entry'],
            departure_date: isset($data['departure_date']) ? (string) $data['departure_date'] : null,
            worker_type_id: (string) ($data['worker_type_id'] ?? '01'),
            worker_subtype_id: (string) ($data['worker_subtype_id'] ?? '00'),
            contract_type_id: (string) ($data['contract_type_id'] ?? '1'),
            salary: (float) ($data['salary'] ?? 0.0),
            integral_salary: (bool) ($data['integral_salary'] ?? false),
            high_risk_pension: (bool) ($data['high_risk_pension'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true)
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateEmployeeDTO
    {
        return new UpdateEmployeeDTO(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            worker_code: (string) $data['worker_code'],
            identity_document_id: (string) ($data['identity_document_id'] ?? '13'),
            document_number: (string) $data['document_number'],
            first_name: (string) $data['first_name'],
            other_names: isset($data['other_names']) ? (string) $data['other_names'] : null,
            first_surname: (string) $data['first_surname'],
            second_surname: isset($data['second_surname']) ? (string) $data['second_surname'] : null,
            working_country_id: (string) ($data['working_country_id'] ?? '45'),
            work_city_id: isset($data['work_city_id']) ? (string) $data['work_city_id'] : null,
            work_address: (string) $data['work_address'],
            date_entry: (string) $data['date_entry'],
            departure_date: isset($data['departure_date']) ? (string) $data['departure_date'] : null,
            worker_type_id: (string) ($data['worker_type_id'] ?? '01'),
            worker_subtype_id: (string) ($data['worker_subtype_id'] ?? '00'),
            contract_type_id: (string) ($data['contract_type_id'] ?? '1'),
            salary: (float) ($data['salary'] ?? 0.0),
            integral_salary: (bool) ($data['integral_salary'] ?? false),
            high_risk_pension: (bool) ($data['high_risk_pension'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true)
        );
    }
}
