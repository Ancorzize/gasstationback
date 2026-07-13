<?php

namespace App\Modules\Dashboard\Infrastructure\Mappers;

use App\Modules\Dashboard\Application\DTOs\GuardarDashboardRolDTO;

class DashboardConfigMapper
{
    public static function fromArray(
        array $data,
        int $roleId
    ): GuardarDashboardRolDTO {

        return new GuardarDashboardRolDTO(

            role_id: $roleId,

            widgets: $data['widgets']

        );

    }
}