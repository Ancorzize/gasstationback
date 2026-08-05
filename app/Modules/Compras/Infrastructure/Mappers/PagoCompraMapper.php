<?php

namespace App\Modules\Compras\Infrastructure\Mappers;

use App\Modules\Compras\Application\DTOs\CreatePagoCompraDTO;

class PagoCompraMapper
{
    public static function fromArrayToCreateDTO(array $data, int $compraId, int $userId): CreatePagoCompraDTO
    {
        return new CreatePagoCompraDTO(
            compra_id: $compraId,
            user_id: $userId,
            fecha_pago: $data['fecha_pago'],
            monto: (float) $data['monto'],
            metodo_pago: $data['metodo_pago'],
            caja_id: (int) $data['caja_id'],
            observacion: $data['observacion'] ?? null,
        );
    }
}