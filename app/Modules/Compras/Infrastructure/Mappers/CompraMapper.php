<?php

namespace App\Modules\Compras\Infrastructure\Mappers;

use App\Modules\Compras\Application\DTOs\CompraDTO;
use App\Modules\Compras\Application\DTOs\CreateCompraDTO;
use App\Modules\Compras\Application\DTOs\DetalleCompraDTO;
use App\Modules\Compras\Application\DTOs\UpdateCompraDTO;

class CompraMapper
{
    /**
     * @return DetalleCompraDTO[]
     */
    private static function mapDetalles(array $detalles): array
    {
        return collect($detalles)->map(function ($item) {
            return new DetalleCompraDTO(
                producto_id: (int) $item['producto_id'],
                cantidad: (float) $item['cantidad'],
                costo_unitario: (float) $item['costo_unitario'],
                iva: (float) $item['iva'],
                soldicom: (float) $item['soldicom'],
                total: (float) $item['total'],
                iva_valor: (float) $item['iva_valor']
            );
        })->all();
    }

    public static function fromArrayToCreateDTO(array $data, int $userId): CreateCompraDTO
    {
        return new CreateCompraDTO(
            proveedor_id: (int) $data['proveedor_id'],
            bodega_id: (int) $data['bodega_id'],
            user_id: $userId,
            numero_documento: $data['numero_documento'] ?? null,
            fecha_compra: $data['fecha_compra'],
            fecha_vencimiento: $data['fecha_vencimiento'] ?? null,
            tipo_pago: $data['tipo_pago'],
            impuesto: (float) ($data['impuesto'] ?? 0),
            observacion: $data['observacion'] ?? null,
            detalles: self::mapDetalles($data['detalles']),
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateCompraDTO
    {
        return new UpdateCompraDTO(
            proveedor_id: (int) $data['proveedor_id'],
            bodega_id: (int) $data['bodega_id'],
            numero_documento: $data['numero_documento'] ?? null,
            fecha_compra: $data['fecha_compra'],
            fecha_vencimiento: $data['fecha_vencimiento'] ?? null,
            tipo_pago: $data['tipo_pago'],
            impuesto: (float) ($data['impuesto'] ?? 0),
            observacion: $data['observacion'] ?? null,
            detalles: self::mapDetalles($data['detalles']),
        );
    }
}