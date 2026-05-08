<?php

namespace App\Modules\Ventas\Application\DTOs;

class CreateVentaDTO
{
    /**
     * @param DetalleVentaDTO[] $detalles
     * @param PagoVentaDTO[] $pagos
     */
    public function __construct(
        public ?int $cliente_id,
        public int $user_id,
        public string $tipo_venta,
        public ?string $observacion,
        public array $detalles,
        public array $pagos,
    ) {}
}