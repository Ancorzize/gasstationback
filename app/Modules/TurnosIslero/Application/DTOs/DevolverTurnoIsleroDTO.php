<?php

namespace App\Modules\TurnosIslero\Application\DTOs;

class DevolverTurnoIsleroDTO
{
    public function __construct(
        public int $turno_id,
        public int $user_id,
        public string $observacion_devolucion,
    ) {}
}