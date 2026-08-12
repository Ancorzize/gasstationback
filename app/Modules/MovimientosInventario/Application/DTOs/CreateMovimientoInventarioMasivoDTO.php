<?php

namespace App\Modules\MovimientosInventario\Application\DTOs;

class CreateMovimientoInventarioMasivoDTO
{
    /**
     * @param MovimientoInventarioMasivoItemDTO[] $items
     */
    public function __construct(
        public int $bodega_origen_id,
        public int $bodega_destino_id,
        public array $items,
        public ?string $observacion,
        public int $user_id,
    ) {}
}