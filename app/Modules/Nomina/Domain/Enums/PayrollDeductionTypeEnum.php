<?php

namespace App\Modules\Nomina\Domain\Enums;

enum PayrollDeductionTypeEnum: string
{
    case HEALTH = 'HEALTH';
    case PENSION = 'PENSION';

    public function label(): string
    {
        return match ($this) {
            self::HEALTH => 'Aporte Salud (4%)',
            self::PENSION => 'Aporte Pensión (4%)',
        };
    }
}
