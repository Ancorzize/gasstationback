<?php

namespace App\Modules\Dashboard\Application\DTOs;

class GuardarDashboardRolDTO
{
    public function __construct(

        public int $role_id,

        public array $widgets

    ) {}
}