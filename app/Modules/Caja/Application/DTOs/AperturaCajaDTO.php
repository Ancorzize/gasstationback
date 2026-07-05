<?php

namespace App\Modules\Caja\Application\DTOs;

class AperturaCajaDTO
{
    public function __construct(
        public string $nombre,
        public string $tipo_caja,
        public int $destino_recaudo_id,
        public float $monto_apertura,
        public ?string $observacion_apertura,
        public int $user_id,
    ) {}
}