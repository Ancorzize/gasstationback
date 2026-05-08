<?php

namespace App\Modules\TurnosIslero\Application\DTOs;

class AbrirTurnoIsleroDTO
{
    public function __construct(
        public int $estacion_id,
        public int $user_id,
        public ?string $observacion_apertura,
        public array $lecturas_iniciales = [],
    ) {}
}