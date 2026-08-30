<?php

namespace App\Modules\TurnosIslero\Application\DTOs;

class AprobarCierreTurnoIsleroDTO
{
    public function __construct(
        public int $turno_id,
        public int $user_id,
    ) {}
}