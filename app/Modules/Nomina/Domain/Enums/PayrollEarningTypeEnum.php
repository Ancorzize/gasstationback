<?php

namespace App\Modules\Nomina\Domain\Enums;

enum PayrollEarningTypeEnum: string
{
    case BASIC_SALARY = 'BASIC_SALARY';
    case TRANSPORT_ASSISTANCE = 'TRANSPORT_ASSISTANCE';

    public function label(): string
    {
        return match ($this) {
            self::BASIC_SALARY => 'Salario Básico',
            self::TRANSPORT_ASSISTANCE => 'Auxilio de Transporte',
        };
    }
}
